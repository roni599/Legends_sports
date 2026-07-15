<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['client', 'ground', 'slots'])->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('client', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date')) {
            $date = $request->date;
            $query->whereHas('slots', function($q) use ($date) {
                $q->whereDate('date', $date);
            });
        }
        
        return $query->paginate(10);
    }

    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'ground_id' => 'required|exists:grounds,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_booking_id' => 'nullable|exists:bookings,id'
        ]);

        $ground = \App\Models\Ground::findOrFail($validated['ground_id']);
        if ($ground->status !== 'active') {
            return response()->json([
                'available' => false,
                'message' => "This ground is currently not available for booking (Status: {$ground->status})."
            ]);
        }

        // Check if any slot overlaps with the requested time for the given ground
        $conflict = \App\Models\BookingSlot::whereHas('booking', function($q) use ($validated) {
            $q->where('ground_id', $validated['ground_id'])
              ->whereIn('status', ['pending', 'confirmed', 'running']); // exclude cancelled
            
            if (isset($validated['exclude_booking_id'])) {
                $q->where('id', '!=', $validated['exclude_booking_id']);
            }
        })
        ->where('date', $validated['date'])
        ->where('start_time', '<', $validated['end_time'])
        ->where('end_time', '>', $validated['start_time'])
        ->exists();

        return response()->json([
            'available' => !$conflict,
            'message' => $conflict ? 'The selected time slot is already booked.' : 'Time slot is available.'
        ]);
    }

    public function calculatePrice(Request $request)
    {
        $validated = $request->validate([
            'ground_id' => 'required|exists:grounds,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $ground = \App\Models\Ground::findOrFail($validated['ground_id']);
        
        $start = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        $durationHours = $end->diffInMinutes($start) / 60;

        $basePrice = $ground->base_price_per_hour * $durationHours;
        $totalPrice = $basePrice;

        $appliedRules = [];

        // Fetch active rules applicable to this ground or all grounds
        $rules = \App\Models\PricingRule::where('status', 'active')
            ->where(function($q) use ($ground) {
                $q->where('ground_id', $ground->id)->orWhereNull('ground_id');
            })->get();

        foreach ($rules as $rule) {
            $applies = false;
            
            if ($rule->type === 'weekend') {
                // In BD, weekend is Friday (5) or Saturday (6)
                $dayOfWeek = $start->dayOfWeekIso;
                if ($dayOfWeek == 5 || $dayOfWeek == 6) {
                    $applies = true;
                }
            } elseif ($rule->type === 'peak_hour') {
                // Check if booking overlaps with peak hour
                $ruleStart = \Carbon\Carbon::parse($validated['date'] . ' ' . $rule->start_time);
                $ruleEnd = \Carbon\Carbon::parse($validated['date'] . ' ' . $rule->end_time);
                
                if ($start < $ruleEnd && $end > $ruleStart) {
                    $applies = true;
                }
            } elseif ($rule->type === 'tournament') {
                // This would typically be a manual toggle from the frontend, but we'll assume false for standard calculations
                $applies = $request->input('is_tournament', false);
            }

            if ($applies) {
                $totalPrice += $rule->price_modifier;
                $appliedRules[] = [
                    'name' => $rule->name,
                    'modifier' => $rule->price_modifier
                ];
            }
        }

        // Ensure total price doesn't go below 0 due to discounts
        $totalPrice = max(0, $totalPrice);

        return response()->json([
            'duration_hours' => $durationHours,
            'base_price' => $basePrice,
            'applied_rules' => $appliedRules,
            'total_price' => $totalPrice
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'ground_id' => 'required|exists:grounds,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $ground = \App\Models\Ground::findOrFail($validated['ground_id']);
        if ($ground->status !== 'active') {
            return response()->json(['message' => 'Ground is not available.'], 422);
        }

        // 1. Check Availability
        $conflict = \App\Models\BookingSlot::whereHas('booking', function($q) use ($validated) {
            $q->where('ground_id', $validated['ground_id'])
              ->whereIn('status', ['pending', 'confirmed', 'running']);
        })
        ->where('date', $validated['date'])
        ->where('start_time', '<', $validated['end_time'])
        ->where('end_time', '>', $validated['start_time'])
        ->exists();

        if ($conflict) {
            return response()->json([
                'errors' => ['time_slot' => ['The selected time slot is already booked.']]
            ], 422);
        }

        // 2. Calculate Price (Reusing logic from calculatePrice)
        $start = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        $durationHours = $end->diffInMinutes($start) / 60;
        $totalAmount = $ground->base_price_per_hour * $durationHours;

        $rules = \App\Models\PricingRule::where('status', 'active')
            ->where(function($q) use ($ground) {
                $q->where('ground_id', $ground->id)->orWhereNull('ground_id');
            })->get();

        foreach ($rules as $rule) {
            $applies = false;
            if ($rule->type === 'weekend') {
                $dayOfWeek = $start->dayOfWeekIso;
                if ($dayOfWeek == 5 || $dayOfWeek == 6) $applies = true;
            } elseif ($rule->type === 'peak_hour') {
                $ruleStart = \Carbon\Carbon::parse($validated['date'] . ' ' . $rule->start_time);
                $ruleEnd = \Carbon\Carbon::parse($validated['date'] . ' ' . $rule->end_time);
                if ($start < $ruleEnd && $end > $ruleStart) $applies = true;
            }
            if ($applies) $totalAmount += $rule->price_modifier;
        }
        $totalAmount = max(0, $totalAmount);

        // 3. Financial calculations
        $discount = $validated['discount'] ?? 0;
        $netAmount = max(0, $totalAmount - $discount);
        $paidAmount = $validated['paid_amount'] ?? 0;
        
        if ($paidAmount > $netAmount) {
            return response()->json([
                'errors' => ['paid_amount' => ['Paid amount (৳' . $paidAmount . ') cannot exceed the net amount (৳' . $netAmount . ').']]
            ], 422);
        }
        
        $dueAmount = $netAmount - $paidAmount;
        
        $status = $paidAmount > 0 ? 'confirmed' : 'pending';

        // 4. Save to DB with Transaction
        $booking = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $totalAmount, $discount, $netAmount, $paidAmount, $dueAmount, $status) {
            $booking = \App\Models\Booking::create([
                'client_id' => $validated['client_id'],
                'ground_id' => $validated['ground_id'],
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'net_amount' => $netAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status
            ]);

            $booking->slots()->create([
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'price' => $netAmount,
                'status' => 'booked'
            ]);

            if ($dueAmount > 0) {
                $client = \App\Models\Client::find($validated['client_id']);
                $client->increment('total_due', $dueAmount);
            }

            return $booking;
        });

        return response()->json($booking->load(['client', 'ground', 'slots']), 201);
    }

    public function show(Booking $booking)
    {
        return $booking->load(['client', 'ground', 'slots']);
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,running,completed,cancelled,no_show',
            'additional_payment' => 'nullable|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $booking) {
            $client = \App\Models\Client::find($booking->client_id);
            $statusChangedToCancelled = isset($validated['status']) && 
                                        $validated['status'] === 'cancelled' && 
                                        $booking->status !== 'cancelled';

            // 1. Handle Cancellation
            if ($statusChangedToCancelled) {
                // If cancelled, the client no longer owes the due amount for this booking
                if ($booking->due_amount > 0) {
                    $client->decrement('total_due', $booking->due_amount);
                }
                
                $booking->update([
                    'status' => 'cancelled',
                    'due_amount' => 0 // Due is forgiven on cancellation
                ]);
                
                // Update slots status to blocked/cancelled so they free up
                $booking->slots()->update(['status' => 'blocked']);
                return;
            }

            // 2. Handle Additional Payment
            if (isset($validated['additional_payment']) && $validated['additional_payment'] > 0) {
                $payment = $validated['additional_payment'];
                
                if ($payment > $booking->due_amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'additional_payment' => ['Payment cannot exceed the due amount of ' . $booking->due_amount]
                    ]);
                }

                $booking->paid_amount += $payment;
                $booking->due_amount -= $payment;
                
                if ($booking->due_amount == 0 && $booking->status === 'pending') {
                    $booking->status = 'confirmed';
                }

                $booking->save();

                // Reduce client's total due
                $client->decrement('total_due', $payment);
            }

            // 3. Handle General Status Update
            if (isset($validated['status']) && $validated['status'] !== 'cancelled') {
                $booking->update(['status' => $validated['status']]);
            }
        });

        return response()->json($booking->fresh()->load(['client', 'ground', 'slots']));
    }

    public function destroy(Booking $booking)
    {
        if (in_array($booking->status, ['running', 'completed'])) {
            return response()->json([
                'message' => 'Cannot delete a running or completed booking to preserve accounting integrity.'
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
            // Reverse the due amount from the client's ledger if the booking is not already cancelled
            if ($booking->status !== 'cancelled' && $booking->due_amount > 0) {
                $client = \App\Models\Client::find($booking->client_id);
                if ($client) {
                    $client->decrement('total_due', $booking->due_amount);
                }
            }
            
            $booking->delete();
        });

        return response()->noContent();
    }
}
