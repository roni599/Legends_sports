<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return (int)$h * 60 + (int)$m;
    }

    private function hasConflict($groundId, $date, $startTime, $endTime, $excludeBookingId = null)
    {
        $newStart = $this->timeToMinutes($startTime);
        $newEnd = $this->timeToMinutes($endTime);
        if ($newEnd <= $newStart) $newEnd += 1440;

        $slots = \App\Models\BookingSlot::whereHas('booking', function ($q) use ($groundId, $excludeBookingId) {
            $q->where('ground_id', $groundId)->whereIn('status', ['pending', 'confirmed', 'running']);
            if ($excludeBookingId) $q->where('id', '!=', $excludeBookingId);
        })->where('date', $date)->get();

        foreach ($slots as $slot) {
            $sStart = $this->timeToMinutes($slot->start_time);
            $sEnd = $this->timeToMinutes($slot->end_time);
            if ($sEnd <= $sStart) $sEnd += 1440;

            if ($newStart < $sEnd && $sStart < $newEnd) return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        Booking::completeExpired();

        $query = Booking::with(['client', 'ground', 'slots'])->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
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
        if ($request->has('all')) {
            return $query->get();
        }
        
        return $query->paginate(10);
    }

    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'ground_id' => 'required|exists:grounds,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'exclude_booking_id' => 'nullable|exists:bookings,id'
        ]);

        if ($validated['end_time'] === $validated['start_time']) {
            return response()->json(['errors' => ['time_slot' => ['End time must be different from start time.']]], 422);
        }

        $start = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($end->lte($start)) $end->addDay();
        $durationMinutes = $end->diffInMinutes($start);

        if ($durationMinutes < 30 || $durationMinutes % 30 !== 0) {
            return response()->json([
                'errors' => ['time_slot' => ['Bookings must be in multiples of 30 minutes (e.g., 1 hour, 1.5 hours).']]
            ], 422);
        }

        $ground = \App\Models\Ground::findOrFail($validated['ground_id']);
        if ($ground->status !== 'active') {
            return response()->json([
                'available' => false,
                'message' => "This ground is currently not available for booking (Status: {$ground->status})."
            ]);
        }

        $conflict = $this->hasConflict(
            $validated['ground_id'],
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['exclude_booking_id'] ?? null
        );

        return response()->json([
            'available' => !$conflict,
            'message' => $conflict ? 'The selected time slot is already booked.' : 'Time slot is available.'
        ]);
    }

    public function calculatePrice(Request $request)
    {
        $validated = $request->validate([
            'ground_id' => 'required|exists:grounds,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'pricing_rule_id' => 'required|exists:pricing_rules,id'
        ]);

        $ground = \App\Models\Ground::findOrFail($validated['ground_id']);
        
        $start = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($end->lte($start)) $end->addDay();
        $durationMinutes = $end->diffInMinutes($start);

        if ($durationMinutes < 30 || $durationMinutes % 30 !== 0) {
            return response()->json([
                'errors' => ['time_slot' => ['Bookings must be in multiples of 30 minutes (e.g., 1 hour, 1.5 hours).']]
            ], 422);
        }

        $durationHours = $durationMinutes / 60;
        $appliedRules = [];

        $rule = \App\Models\PricingRule::findOrFail($validated['pricing_rule_id']);
        $basePrice = $rule->price_modifier;
        $totalPrice = $basePrice * $durationHours;
        $appliedRules[] = [
            'name' => $rule->name,
            'modifier' => $rule->price_modifier
        ];

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
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'pricing_rule_id' => 'required|exists:pricing_rules,id',
        ]);

        if ($validated['end_time'] === $validated['start_time']) {
            return response()->json(['message' => 'End time must be different from start time.'], 422);
        }

        $ground = \App\Models\Ground::findOrFail($validated['ground_id']);
        if ($ground->status !== 'active') {
            return response()->json(['message' => 'Ground is not available.'], 422);
        }

        // 1.5. Check Client Credit Limit
        $clientForCheck = \App\Models\Client::findOrFail($validated['client_id']);
        if ($clientForCheck->total_due >= 10000) {
            return response()->json([
                'errors' => [
                    'client_id' => [
                        'This client has exceeded the maximum credit limit (৳10,000). Their current due is ৳' . 
                        $clientForCheck->total_due . '. Please collect due payments before accepting new bookings.'
                    ]
                ]
            ], 422);
        }

        // 2. Calculate Price
        $start = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($end->lte($start)) $end->addDay();
        $durationMinutes = $end->diffInMinutes($start);

        if ($durationMinutes < 30 || $durationMinutes % 30 !== 0) {
            return response()->json([
                'errors' => ['time_slot' => ['Bookings must be in multiples of 30 minutes (e.g., 1 hour, 1.5 hours).']]
            ], 422);
        }

        $durationHours = $durationMinutes / 60;

        $appliedRules = [];

        $rule = \App\Models\PricingRule::findOrFail($validated['pricing_rule_id']);
        $totalAmount = $rule->price_modifier * $durationHours;
        $appliedRules[] = [
            'name' => $rule->name,
            'type' => $rule->type,
            'modifier' => $rule->price_modifier
        ];
        $totalAmount = max(0, $totalAmount);

        // 3. Financial calculations
        $discount = $validated['discount'] ?? 0;
        
        if ($discount > $totalAmount) {
            return response()->json([
                'errors' => ['discount' => ['Discount (৳' . $discount . ') cannot exceed the total amount (৳' . $totalAmount . ').']]
            ], 422);
        }
        
        $netAmount = max(0, $totalAmount - $discount);
        
        $requestedPaidAmount = $validated['paid_amount'] ?? 0;
        
        // SECURITY FIX: Ensure the recorded paid amount does not exceed the net amount
        // If a customer pays a 1000 note for an 800 bill, the revenue is 800 (200 is change)
        $paidAmount = min($requestedPaidAmount, $netAmount);
        
        $dueAmount = $netAmount - $paidAmount;
        
        $status = 'pending';

        // 4. Save to DB with Transaction and Pessimistic Locking
        $booking = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $totalAmount, $discount, $netAmount, $paidAmount, $dueAmount, $status, $ground, $appliedRules) {
            
            // Pessimistic Lock on Ground to prevent Race Conditions (Double Booking)
            \App\Models\Ground::where('id', $ground->id)->lockForUpdate()->first();

            // 1. Check Availability (Inside Transaction)
            if ($this->hasConflict($validated['ground_id'], $validated['date'], $validated['start_time'], $validated['end_time'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'time_slot' => ['The selected time slot is already booked.']
                ]);
            }
            $booking = \App\Models\Booking::create([
                'client_id' => $validated['client_id'],
                'ground_id' => $validated['ground_id'],
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'net_amount' => $netAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
                'applied_rules' => json_encode($appliedRules)
            ]);

            $booking->slots()->create([
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'price' => $netAmount,
                'status' => 'booked'
            ]);

            if ($paidAmount > 0) {
                \App\Models\Payment::create([
                    'client_id' => $validated['client_id'],
                    'amount' => $paidAmount,
                    'type' => 'book pay',
                    'payment_method' => 'cash',
                    'transaction_id' => 'BKG-' . $booking->id
                ]);
            }

            if ($dueAmount > 0) {
                $client = \App\Models\Client::lockForUpdate()->find($validated['client_id']);
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
            'refund_amount' => 'nullable|numeric|min:0'
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $booking) {
            $lockedBooking = \App\Models\Booking::lockForUpdate()->find($booking->id);
            
            if ($lockedBooking->status === 'cancelled') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => ['A cancelled booking cannot be modified or restored. Please create a new booking.']
                ]);
            }
            
            $client = \App\Models\Client::lockForUpdate()->find($lockedBooking->client_id);
            $statusChangedToCancelled = isset($validated['status']) && 
                                        $validated['status'] === 'cancelled' && 
                                        $lockedBooking->status !== 'cancelled';

            // 1. Handle Cancellation and Refund
            if ($statusChangedToCancelled) {
                if (in_array($lockedBooking->status, ['running', 'completed'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => ['Cannot cancel a booking that is already running or completed to preserve accounting integrity.']
                    ]);
                }

                $refundAmount = $validated['refund_amount'] ?? 0;
                
                if ($refundAmount > $lockedBooking->paid_amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'refund_amount' => ['Refund cannot exceed the previously paid amount of ' . $lockedBooking->paid_amount]
                    ]);
                }

                // If cancelled, the client no longer owes the due amount for this booking
                if ($lockedBooking->due_amount > 0) {
                    $client->decrement('total_due', $lockedBooking->due_amount);
                }
                
                $lockedBooking->update([
                    'status' => 'cancelled',
                    'due_amount' => 0, // Due is forgiven on cancellation
                    'refund_amount' => $refundAmount
                ]);
                
                // Record the cash going OUT of the drawer for accurate accounting
                if ($refundAmount > 0) {
                    \App\Models\Payment::create([
                        'client_id' => $lockedBooking->client_id,
                        'amount' => $refundAmount,
                        'type' => 'out',
                        'payment_method' => 'cash',
                        'transaction_id' => 'BKG-REF-' . $lockedBooking->id
                    ]);
                }
                
                // Update slots status to blocked/cancelled so they free up
                $lockedBooking->slots()->update(['status' => 'blocked']);
                return;
            }

            // 2. Handle Additional Payment
            if (isset($validated['additional_payment']) && $validated['additional_payment'] > 0) {
                $payment = $validated['additional_payment'];
                
                if ($payment > $lockedBooking->due_amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'additional_payment' => ['Payment cannot exceed the due amount of ' . $lockedBooking->due_amount]
                    ]);
                }

                $lockedBooking->paid_amount += $payment;
                $lockedBooking->due_amount -= $payment;
                
                if ($lockedBooking->due_amount == 0 && $lockedBooking->status === 'pending') {
                    $lockedBooking->status = 'confirmed';
                }

                $lockedBooking->save();

                // Reduce client's total due
                $client->decrement('total_due', $payment);
                
                // Create Payment record for accurate daily cash reconciliation
                \App\Models\Payment::create([
                    'client_id' => $lockedBooking->client_id,
                    'amount' => $payment,
                    'type' => 'book pay',
                    'payment_method' => 'cash',
                    'transaction_id' => 'BKG-UPD-' . $lockedBooking->id . '-' . uniqid()
                ]);
            }

            // 3. Handle General Status Update
            if (isset($validated['status']) && $validated['status'] !== 'cancelled') {
                $lockedBooking->update(['status' => $validated['status']]);
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

        if ($booking->paid_amount > 0) {
            return response()->json([
                'message' => 'Cannot delete a booking that has received payments. Please cancel it instead or refund the amount.'
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
            // Reverse the due amount from the client's ledger if the booking is not already cancelled
            if ($booking->status !== 'cancelled' && $booking->due_amount > 0) {
                $client = \App\Models\Client::lockForUpdate()->find($booking->client_id);
                if ($client) {
                    $client->decrement('total_due', $booking->due_amount);
                }
            }
            
            $booking->delete();
        });

        return response()->noContent();
    }
}
