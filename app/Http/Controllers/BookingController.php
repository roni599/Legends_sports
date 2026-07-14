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
        ]);

        // Check if any slot overlaps with the requested time for the given ground
        $conflict = \App\Models\BookingSlot::whereHas('booking', function($q) use ($validated) {
            $q->where('ground_id', $validated['ground_id'])
              ->whereIn('status', ['pending', 'confirmed', 'running']); // exclude cancelled
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
