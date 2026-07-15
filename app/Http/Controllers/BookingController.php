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
        // To be implemented in next step
    }

    public function show(Booking $booking)
    {
        return $booking->load(['client', 'ground', 'slots']);
    }

    public function update(Request $request, Booking $booking)
    {
        // To be implemented in next step
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->noContent();
    }
}
