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
        if ($client->total_due != 0 || \App\Models\Booking::where('client_id', $client->id)->exists() || \App\Models\Invoice::where('client_id', $client->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete! Client has transactions or dues.'
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
            ->orderBy('created_at', 'desc')
            ->get();

        $totalBookedAmount = 0;
        $totalPlayAmount = 0;
        $totalPaid = 0;
        $totalDue = $client->total_due > 0 ? $client->total_due : 0;
        $totalAdvance = $client->total_due < 0 ? abs($client->total_due) : 0;
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

    public function getDueInvoices(Client $client)
    {
        $invoices = \App\Models\Invoice::where('client_id', $client->id)
            ->where('due', '>', 0)
            ->get()->map(function ($inv) {
                return [
                    'id' => 'INV-' . $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'grand_total' => $inv->grand_total,
                    'due' => $inv->due,
                    'type' => 'invoice',
                    'date' => $inv->created_at ? $inv->created_at->format('jS F') : null,
                    'original_id' => $inv->id
                ];
            });
            
        $bookings = \App\Models\Booking::where('client_id', $client->id)
            ->where('due_amount', '>', 0)
            ->get()->map(function ($bkg) {
                return [
                    'id' => 'BKG-' . $bkg->id,
                    'invoice_number' => 'BKG-' . $bkg->id,
                    'grand_total' => $bkg->net_amount,
                    'due' => $bkg->due_amount,
                    'type' => 'booking',
                    'date' => $bkg->created_at ? $bkg->created_at->format('jS F') : null,
                    'original_id' => $bkg->id
                ];
            });

        return response()->json([
            'client' => $client,
            'invoices' => collect($invoices)->merge($bookings)->values()
        ]);
    }

    public function receiveDueInvoices(Request $request, Client $client)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bkash,bank,card',
            'note' => 'nullable|string',
            'date' => 'required|date',
            'invoices' => 'nullable|array',
            'invoices.*.id' => 'required_with:invoices',
            'invoices.*.type' => 'nullable|string',
            'invoices.*.original_id' => 'nullable|integer',
            'invoices.*.amount' => 'required_with:invoices|numeric|min:0.01',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validated['amount'] > max(0, $client->total_due)) {
            return response()->json(['message' => 'Amount cannot exceed the total due.'], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($client, $validated) {
            $lockedClient = Client::lockForUpdate()->find($client->id);
            $totalPaid = 0;

            if (!empty($validated['invoices'])) {
                foreach ($validated['invoices'] as $invoiceData) {
                    if (isset($invoiceData['type']) && $invoiceData['type'] === 'booking') {
                        $booking = \App\Models\Booking::lockForUpdate()->find($invoiceData['original_id']);
                        if (!$booking || $booking->client_id !== $lockedClient->id) continue;
                        
                        $payAmount = min($invoiceData['amount'], $booking->due_amount);
                        if ($payAmount <= 0) continue;

                        $booking->paid_amount += $payAmount;
                        $booking->due_amount -= $payAmount;
                        $booking->save();
                    } else {
                        $invoice = \App\Models\Invoice::lockForUpdate()->find($invoiceData['original_id'] ?? $invoiceData['id']);
                        if (!$invoice || $invoice->client_id !== $lockedClient->id) continue;
                        
                        $payAmount = min($invoiceData['amount'], $invoice->due);
                        if ($payAmount <= 0) continue;

                        $invoice->paid += $payAmount;
                        $invoice->due -= $payAmount;
                        $invoice->save();
                    }

                    $totalPaid += $payAmount;
                }
            }

            // If user inputted an amount greater than the sum of selected invoices (or no invoices were selected),
            // use the manually entered amount as the total paid.
            $finalPaymentAmount = max($totalPaid, $validated['amount']);

            if ($finalPaymentAmount > 0) {
                \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'amount' => $finalPaymentAmount,
                    'type' => 'due receive',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => 'INV-PAY-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'],
                    'updated_at' => $validated['date']
                ]);

                $lockedClient->total_due -= $finalPaymentAmount;
                $lockedClient->save();
            }
        });

        return response()->json(['message' => 'Payment received successfully.', 'client' => $client->fresh()]);
    }

    public function payOut(Request $request, Client $client)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bkash,bank,card',
            'note' => 'nullable|string',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $maxPayable = $client->total_due < 0 ? abs($client->total_due) : 0;
        if ($validated['amount'] > $maxPayable) {
            return response()->json(['message' => 'Amount cannot exceed the total advance payable.'], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($client, $validated) {
            $lockedClient = Client::lockForUpdate()->find($client->id);

            \App\Models\Payment::create([
                'client_id' => $lockedClient->id,
                'amount' => $validated['amount'],
                'type' => 'due pay',
                'payment_method' => $validated['payment_method'],
                'transaction_id' => 'PAY-OUT-' . $lockedClient->id . '-' . uniqid(),
                'created_at' => $validated['date'],
                'updated_at' => $validated['date']
            ]);

            // Paying the client increases their due (or reduces their advance)
            $lockedClient->total_due += $validated['amount'];
            $lockedClient->save();
        });

        return response()->json(['message' => 'Payment sent successfully.', 'client' => $client->fresh()]);
    }

    public function processAdvance(Request $request, Client $client)
    {
        $validated = $request->validate([
            'receiving_advance' => 'nullable|numeric|min:0',
            'refund_advance' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bkash,bank,card',
            'note' => 'nullable|string',
            'date' => 'required|date'
        ]);
        
        $currentAdvance = $client->total_due < 0 ? abs($client->total_due) : 0;
        
        if (!empty($validated['refund_advance']) && $validated['refund_advance'] > $currentAdvance) {
            return response()->json(['message' => 'Refund amount cannot exceed the total advance amount.'], 422);
        }

        $createdPaymentIds = [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($client, $validated, &$createdPaymentIds) {
            $lockedClient = Client::lockForUpdate()->find($client->id);

            if (!empty($validated['receiving_advance']) && $validated['receiving_advance'] > 0) {
                $payment = \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'amount' => $validated['receiving_advance'],
                    'type' => 'advance receive',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => 'ADV-REC-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'],
                    'updated_at' => $validated['date']
                ]);
                $createdPaymentIds[] = $payment->id;
                // Receiving advance reduces total due (increases advance balance)
                $lockedClient->total_due -= $validated['receiving_advance'];
            }

            if (!empty($validated['refund_advance']) && $validated['refund_advance'] > 0) {
                $payment = \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'amount' => $validated['refund_advance'],
                    'type' => 'advance refund',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => 'ADV-REF-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'],
                    'updated_at' => $validated['date']
                ]);
                $createdPaymentIds[] = $payment->id;
                // Refunding advance increases total due (decreases advance balance)
                $lockedClient->total_due += $validated['refund_advance'];
            }
            
            $lockedClient->save();
        });

        return response()->json([
            'message' => 'Advance processed successfully.', 
            'client' => $client->fresh(),
            'payments' => $createdPaymentIds
        ]);
    }

    public function dismissDueInvoices(Request $request, Client $client)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:dismiss',
            'note' => 'nullable|string',
            'date' => 'required|date',
            'invoices' => 'nullable|array',
            'invoices.*.id' => 'required_with:invoices|exists:invoices,id',
            'invoices.*.amount' => 'required_with:invoices|numeric|min:0.01',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validated['amount'] > max(0, $client->total_due)) {
            return response()->json(['message' => 'Amount cannot exceed the total due.'], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($client, $validated) {
            $lockedClient = Client::lockForUpdate()->find($client->id);
            $totalDismissed = 0;

            if (!empty($validated['invoices'])) {
                foreach ($validated['invoices'] as $invoiceData) {
                    $invoice = \App\Models\Invoice::lockForUpdate()->find($invoiceData['id']);
                    if ($invoice->client_id !== $lockedClient->id) continue;
                    
                    $dismissAmount = min($invoiceData['amount'], $invoice->due);
                    if ($dismissAmount <= 0) continue;

                    $invoice->due -= $dismissAmount;
                    $invoice->save();

                    $totalDismissed += $dismissAmount;
                }
            }

            $finalAmount = max($totalDismissed, $validated['amount']);

            if ($finalAmount > 0) {
                \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'amount' => $finalAmount,
                    'type' => 'due dismiss',
                    'payment_method' => 'dismiss',
                    'transaction_id' => 'DISMISS-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'],
                    'updated_at' => $validated['date']
                ]);

                $lockedClient->total_due -= $finalAmount;
                $lockedClient->save();
            }
        });

        return response()->json(['message' => 'Invoices dismissed successfully.', 'client' => $client->fresh()]);
    }
}
