<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // Lightweight dropdown mode: no eager-loaded bookings, no N+1
        if ($request->has('dropdown')) {
            return Client::select('id', 'name', 'phone', 'total_due')->latest()->get();
        }

        $query = Client::with([
            'bookings.ground:id,name',
            'bookings.slots:id,booking_id,date,start_time,end_time,price'
        ])->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }
        
        $clients = $request->has('all') ? $query->get() : $query->paginate(10);

        $transformClient = function ($cli) {
            $totalPlays = 0;
            $grounds = [];
            $slotDetails = [];
            $totalBilled = 0;
            $totalPaid = 0;
            foreach ($cli->bookings as $bkg) {
                if ($bkg->status !== 'cancelled') {
                    $totalPlays += $bkg->slots->count();
                    $groundName = $bkg->ground ? $bkg->ground->name : 'Unknown';
                    $grounds[$groundName] = true;
                    
                    foreach ($bkg->slots as $slot) {
                        $date = date('d M', strtotime($slot->date));
                        $time = date('h:i A', strtotime($slot->start_time)) . ' - ' . date('h:i A', strtotime($slot->end_time));
                        $slotDetails[] = $groundName . ' | ' . $date . ' ' . $time . ' (৳' . $slot->price . ')';
                    }
                    $totalBilled += $bkg->net_amount;
                    $totalPaid += $bkg->paid_amount;
                }
            }
            
            $displaySlots = count($slotDetails) > 3 ? implode("\n", array_slice($slotDetails, 0, 3)) . "\n...and " . (count($slotDetails) - 3) . " more" : implode("\n", $slotDetails);
            
            $cli->booked_slots = $displaySlots ?: 'No Plays';
            $cli->play_time = $totalPlays;
            $cli->total_billed = $totalBilled;
            $cli->total_paid = $totalPaid;
            $cli->due_amount = $cli->total_due > 0 ? $cli->total_due : 0;
            $cli->advance_amount = $cli->total_due < 0 ? abs($cli->total_due) : 0;
            
            return $cli;
        };

        if ($request->has('all')) {
            $clients->transform($transformClient);
        } else {
            $clients->getCollection()->transform($transformClient);
        }

        return $clients;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:clients,phone',
            'email' => 'nullable|email',
            'address' => 'nullable|string'
        ]);

        // total_due is automatically set to 0 by database default or logic, should not be manually passed.
        $client = Client::create($validated);
        return response()->json($client, 201);
    }

    public function show(Client $client)
    {
        return $client;
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:clients,phone,' . $client->id,
            'email' => 'nullable|email',
            'address' => 'nullable|string'
        ]);

        $client->update($validated);
        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        if (\App\Models\Booking::where('client_id', $client->id)->exists() || \App\Models\Invoice::where('client_id', $client->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete this client because they have a transaction or booking history. Deleting them would result in financial data loss.'
            ], 422);
        }

        $client->delete();
        return response()->json(null, 204);
    }

    public function ledger(Client $client)
    {
        $client->loadCount('bookings');

        $bookings = \App\Models\Booking::with(['ground:id,name', 'slots'])
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $payments = \App\Models\Payment::where('client_id', $client->id)
            ->where('type', 'in')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalBookedAmount = 0;
        $totalPlayAmount = 0;
        $totalPaid = 0;
        $totalDue = 0;
        $totalAdvance = 0;
        $totalBooked = 0;
        $totalPlays = 0;
        $totalBookings = $bookings->count();

        foreach ($bookings as $bkg) {
            $slots = $bkg->slots;
            foreach ($slots as $slot) {
                $totalBookedAmount += floatval($slot->price);
                $totalBooked++;
                if ($bkg->status === 'completed') {
                    $totalPlayAmount += floatval($slot->price);
                    $totalPlays++;
                }
            }
            $totalPaid += floatval($bkg->paid_amount);
            $due = floatval($bkg->total_due);
            if ($due > 0) $totalDue += $due;
            elseif ($due < 0) $totalAdvance += abs($due);
        }

        return response()->json([
            'client' => $client,
            'ledger' => $bookings,
            'payments' => $payments,
            'summary' => [
                'total_bookings' => $totalBookings,
                'total_booked' => $totalBooked,
                'total_plays' => $totalPlays,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'total_advance' => $totalAdvance,
                'total_booked_amount' => $totalBookedAmount,
                'total_play_amount' => $totalPlayAmount,
            ]
        ]);
    }

    public function toggleStatus(Client $client)
    {
        $client->status = $client->status === 'active' ? 'deactive' : 'active';
        $client->save();
        return response()->json(['status' => $client->status]);
    }

    public function receiveDuePayment(Request $request, Client $client)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bkash,bank,card'
        ]);

        if ($validated['amount'] > $client->total_due) {
            return response()->json(['message' => 'Payment amount cannot exceed the total due.'], 422);
        }

        DB::transaction(function () use ($client, $validated) {
            $lockedClient = Client::lockForUpdate()->find($client->id);
            
            // Create Payment record
            \App\Models\Payment::create([
                'client_id' => $lockedClient->id,
                'amount' => $validated['amount'],
                'type' => 'in',
                'payment_method' => $validated['payment_method'],
                'transaction_id' => 'DUE-' . $lockedClient->id . '-' . uniqid()
            ]);

            // Reduce total_due
            $lockedClient->total_due -= $validated['amount'];
            $lockedClient->save();
        });

        return response()->json(['message' => 'Due payment received successfully.', 'client' => $client]);
    }
}
