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
        ])
        ->withSum(['payments as total_advance_received' => function($query) {
            $query->where('type', 'advance receive');
        }], 'amount')
        ->withSum(['payments as total_refund_paid' => function($query) {
            $query->where('type', 'advance refund');
        }], 'amount')
        ->latest();
        
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
            $cli->due_amount = $cli->total_due;
            $cli->advance_amount = $cli->total_advance_received ?? 0;
            $cli->refund_amount = $cli->total_refund_paid ?? 0;
            
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

        $bookings = \App\Models\Booking::with(['ground:id,name', 'slots', 'invoice:id,booking_id,invoice_number'])
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $payments = \App\Models\Payment::with(['user', 'invoice.booking.ground', 'invoice.booking.slots'])
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalBookedAmount = 0;
        $totalPlayAmount = 0;
        $totalPaid = 0;
        $totalDiscount = 0;
        $totalDue = $client->total_due;
        $totalAdvance = \App\Models\Payment::where('client_id', $client->id)->where('type', 'advance receive')->sum('amount');
        $totalRefund = \App\Models\Payment::where('client_id', $client->id)->where('type', 'advance refund')->sum('amount');
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
            $totalDiscount += floatval($bkg->discount);
        }

        $items = collect();
        foreach ($bookings as $bkg) {
            $display_status = $bkg->status;
            if ($bkg->status === 'cancelled') {
                $display_status = 'confirmed';
            }
            $items->push([
                'date' => $bkg->created_at,
                'model' => $bkg,
                'amount' => floatval($bkg->net_amount ?? $bkg->total_amount),
                'is_addition' => true,
                'display_status' => strtoupper($display_status)
            ]);
        }

        foreach ($payments as $payment) {
            $is_addition = false;
            if (in_array($payment->type, ['due pay', 'advance refund', 'out', 'penalty'])) {
                $is_addition = true;
            }
            
            $display_status = 'CONFIRMED';
            if (in_array($payment->type, ['cancelled booking', 'out', 'penalty', 'advance refund', 'due dismiss'])) {
                $display_status = 'CANCELLED';
            } elseif ($payment->invoice?->booking?->status === 'completed') {
                $display_status = 'COMPLETED';
            }

            $items->push([
                'date' => $payment->created_at,
                'model' => $payment,
                'amount' => floatval($payment->amount),
                'is_addition' => $is_addition,
                'display_status' => $display_status
            ]);
        }

        $items = $items->sortBy('date');
        $runningDue = 0;
        foreach ($items as $item) {
            if ($item['is_addition']) {
                $runningDue += $item['amount'];
            } else {
                $runningDue -= $item['amount'];
            }
            $item['model']->running_due = $runningDue;
            $item['model']->display_status = $item['display_status'];
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
                'total_discount' => $totalDiscount,
                'total_refund' => $totalRefund,
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
                'user_id' => auth()->id(),
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
            ->where('type', '!=', 'advance')
            ->where('due', '>', 0)
            ->get()->map(function ($inv) {
                return [
                    'id' => 'INV-' . $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'grand_total' => $inv->grand_total,
                    'due' => $inv->due,
                    'type' => $inv->type,
                    'date' => $inv->created_at ? $inv->created_at->format('jS F') : null,
                    'original_id' => $inv->id
                ];
            });

        return response()->json([
            'client' => $client,
            'invoices' => $invoices
        ]);
    }

    public function getAdvanceInvoices(Client $client)
    {
        $invoices = \App\Models\Invoice::where('client_id', $client->id)
            ->where('type', 'advance')
            ->where('due', '<', 0)
            ->get()->map(function ($inv) {
                return [
                    'id' => 'INV-' . $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'grand_total' => $inv->grand_total,
                    'paid' => $inv->paid,
                    'due' => abs($inv->due), // return positive for display
                    'type' => $inv->type,
                    'date' => $inv->created_at ? $inv->created_at->format('jS F') : null,
                    'original_id' => $inv->id
                ];
            });

        return response()->json([
            'client' => $client,
            'invoices' => $invoices
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
                    $invoiceIdForPayment = $invoiceData['original_id'] ?? $invoiceData['id'];
                    $invoice = \App\Models\Invoice::lockForUpdate()->find($invoiceIdForPayment);
                    if (!$invoice || $invoice->client_id !== $lockedClient->id) continue;
                    
                    $payAmount = min($invoiceData['amount'], $invoice->due);
                    if ($payAmount <= 0) continue;

                    $invoice->paid += $payAmount;
                    $invoice->due -= $payAmount;
                    $invoice->save();

                    if ($invoice->type === 'booking' && $invoice->booking_id) {
                        $booking = \App\Models\Booking::find($invoice->booking_id);
                        if ($booking) {
                            $booking->paid_amount += $payAmount;
                            $booking->due_amount -= $payAmount;
                            if ($booking->due_amount == 0 && $booking->status === 'pending') {
                                $booking->status = 'confirmed';
                            }
                            $booking->save();
                        }
                    }

                    $totalPaid += $payAmount;

                    // Create individual payment per invoice/booking
                    \App\Models\Payment::create([
                        'client_id' => $lockedClient->id,
                        'user_id' => auth()->id(),
                        'invoice_id' => $invoiceIdForPayment,
                        'amount' => $payAmount,
                        'type' => 'due receive',
                        'payment_method' => $validated['payment_method'],
                        'transaction_id' => 'INV-PAY-' . $lockedClient->id . '-' . uniqid(),
                        'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                        'updated_at' => $validated['date']
                    ]);
                }
            }

            // If user inputted an amount greater than the sum of selected invoices (or no invoices were selected),
            // use the manually entered amount as the total paid.
            $finalPaymentAmount = max($totalPaid, $validated['amount']);
            $excessAmount = $finalPaymentAmount - $totalPaid;

            if ($excessAmount > 0) {
                \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'user_id' => auth()->id(),
                    'amount' => $excessAmount,
                    'type' => 'due receive',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => 'INV-PAY-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                    'updated_at' => $validated['date']
                ]);
            }
            if ($finalPaymentAmount > 0) {
                $lockedClient->total_due -= $finalPaymentAmount;
                $lockedClient->save();
            }
        });

        return response()->json(['message' => 'Payment received successfully.', 'client' => $client->fresh()]);
    }

    public function getRefundableInvoices(Client $client)
    {
        $invoices = \App\Models\Invoice::where('client_id', $client->id)
            ->where('due', '<', 0)
            ->where('type', '!=', 'advance')
            ->get()->map(function ($inv) {
                return [
                    'id' => 'INV-' . $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'grand_total' => $inv->grand_total,
                    'paid_amount' => $inv->paid,
                    'due' => abs($inv->due), // return positive for frontend compatibility
                    'type' => $inv->type,
                    'date' => $inv->created_at ? $inv->created_at->format('jS F') : null,
                    'original_id' => $inv->id
                ];
            });

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function refundInvoices(Request $request, Client $client)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bkash,bank,card',
            'note' => 'nullable|string',
            'date' => 'required|date',
            'invoices' => 'required|array',
            'invoices.*.id' => 'required',
            'invoices.*.type' => 'required|string',
            'invoices.*.original_id' => 'required|integer',
            'invoices.*.amount' => 'required|numeric|min:0.01',
            'amount' => 'required|numeric|min:0.01',
            'penalty' => 'nullable|numeric|min:0'
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($client, $validated) {
            $lockedClient = Client::lockForUpdate()->find($client->id);
            $remainingPenalty = $validated['penalty'] ?? 0;

            foreach ($validated['invoices'] as $inv) {
                $invoiceIdForPayment = $inv['original_id'] ?? $inv['id'];
                $invoice = \App\Models\Invoice::lockForUpdate()->find($invoiceIdForPayment);
                if (!$invoice || $invoice->client_id !== $lockedClient->id) continue;

                if ($invoice->due < 0) {
                    $maxRefundable = abs($invoice->due);
                    $refundAmount = min($inv['amount'], $maxRefundable);
                    
                    if ($refundAmount > 0) {
                        $invoice->paid -= $refundAmount;
                        $invoice->due += $refundAmount;
                        $invoice->save();

                        if ($invoice->type === 'booking' && $invoice->booking_id) {
                            $bkg = \App\Models\Booking::find($invoice->booking_id);
                            if ($bkg) {
                                $bkg->increment('refund_amount', $refundAmount);
                            }
                        }
                        
                        $thisPenalty = min($refundAmount, $remainingPenalty);
                        $thisOut = $refundAmount - $thisPenalty;
                        $remainingPenalty -= $thisPenalty;

                        if ($thisOut > 0) {
                            \App\Models\Payment::create([
                                'client_id' => $lockedClient->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => auth()->id(),
                                'amount' => $thisOut,
                                'type' => 'out', // money leaving the drawer
                                'payment_method' => $validated['payment_method'],
                                'transaction_id' => 'REFUND-INV-' . $invoice->id,
                                'note' => 'Refund for invoice ' . $invoice->invoice_number . ($validated['note'] ? ' | ' . $validated['note'] : ''),
                                'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                                'updated_at' => $validated['date']
                            ]);
                        }

                        if ($thisPenalty > 0) {
                            \App\Models\Payment::create([
                                'client_id' => $lockedClient->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => auth()->id(),
                                'amount' => $thisPenalty,
                                'type' => 'penalty', // penalty kept by admin
                                'payment_method' => 'system', // Penalty doesn't really use a payment gateway
                                'transaction_id' => 'PENALTY-INV-' . $invoice->id,
                                'note' => 'Penalty charged on invoice ' . $invoice->invoice_number . ($validated['note'] ? ' | ' . $validated['note'] : ''),
                                'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                                'updated_at' => $validated['date']
                            ]);
                        }
                        
                        $lockedClient->increment('total_due', $refundAmount);
                    }
                }
            }
        });

        return response()->json(['message' => 'Refund and penalty processed successfully']);
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
                'user_id' => auth()->id(),
                'amount' => $validated['amount'],
                'type' => 'due pay',
                'payment_method' => $validated['payment_method'],
                'transaction_id' => 'PAY-OUT-' . $lockedClient->id . '-' . uniqid(),
                'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
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
            'date' => 'required|date',
            'invoices' => 'nullable|array',
            'invoices.*.id' => 'required_with:invoices',
            'invoices.*.type' => 'nullable|string',
            'invoices.*.original_id' => 'nullable|integer',
            'invoices.*.amount' => 'required_with:invoices|numeric|min:0.01'
        ]);
        
        $currentAdvance = abs(\App\Models\Invoice::where('client_id', $client->id)->where('type', 'advance')->where('due', '<', 0)->sum('due'));
        
        $refundAmountRequested = $validated['refund_advance'] ?? 0;
        if ($refundAmountRequested > $currentAdvance) {
            return response()->json(['message' => 'Refund amount cannot exceed the total advance amount.'], 422);
        }

        $createdPaymentIds = [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($client, $validated, &$createdPaymentIds, $refundAmountRequested) {
            $lockedClient = Client::lockForUpdate()->find($client->id);

            if (!empty($validated['receiving_advance']) && $validated['receiving_advance'] > 0) {
                $invoice = \App\Models\Invoice::create([
                    'client_id' => $lockedClient->id,
                    'type' => 'advance',
                    'invoice_number' => 'INV-ADV-' . uniqid(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'grand_total' => 0,
                    'paid' => $validated['receiving_advance'],
                    'due' => -$validated['receiving_advance']
                ]);

                $payment = \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'invoice_id' => $invoice->id,
                    'user_id' => auth()->id(),
                    'amount' => $validated['receiving_advance'],
                    'type' => 'advance receive',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => 'ADV-REC-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                    'updated_at' => $validated['date']
                ]);
                $createdPaymentIds[] = $payment->id;
                // We no longer reduce total_due for advances as it is tracked independently via invoice dues
            }

            $totalRefundedFromInvoices = 0;
            if (!empty($validated['invoices'])) {
                foreach ($validated['invoices'] as $invData) {
                    $invoiceIdForPayment = $invData['original_id'] ?? $invData['id'];
                    $invoice = \App\Models\Invoice::lockForUpdate()->find($invoiceIdForPayment);
                    if (!$invoice || $invoice->client_id !== $lockedClient->id || $invoice->type !== 'advance') continue;
                    
                    if ($invoice->due < 0) {
                        $maxRefundable = abs($invoice->due);
                        $refundAmt = min($invData['amount'], $maxRefundable);
                        
                        if ($refundAmt > 0) {
                            $invoice->paid -= $refundAmt;
                            $invoice->due += $refundAmt;
                            $invoice->save();

                            $payment = \App\Models\Payment::create([
                                'client_id' => $lockedClient->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => auth()->id(),
                                'amount' => $refundAmt,
                                'type' => 'advance refund',
                                'payment_method' => $validated['payment_method'],
                                'transaction_id' => 'ADV-REF-INV-' . $invoice->id,
                                'note' => 'Refund for advance invoice ' . $invoice->invoice_number . ($validated['note'] ? ' | ' . $validated['note'] : ''),
                                'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                                'updated_at' => $validated['date']
                            ]);
                            $createdPaymentIds[] = $payment->id;
                            $totalRefundedFromInvoices += $refundAmt;
                        }
                    }
                }
            }

            $finalRefundAmount = max($totalRefundedFromInvoices, $refundAmountRequested);
            $excessRefundAmount = $finalRefundAmount - $totalRefundedFromInvoices;

            if ($excessRefundAmount > 0) {
                $payment = \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'user_id' => auth()->id(),
                    'amount' => $excessRefundAmount,
                    'type' => 'advance refund',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => 'ADV-REF-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                    'updated_at' => $validated['date']
                ]);
                $createdPaymentIds[] = $payment->id;
            }
            // We no longer increase total_due for advance refunds as it is tracked independently
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

                    if ($invoice->type === 'booking' && $invoice->booking_id) {
                        $booking = \App\Models\Booking::find($invoice->booking_id);
                        if ($booking) {
                            $booking->due_amount -= $dismissAmount;
                            if ($booking->due_amount == 0 && $booking->status === 'pending') {
                                $booking->status = 'confirmed';
                            }
                            $booking->save();
                        }
                    }

                    $totalDismissed += $dismissAmount;

                    \App\Models\Payment::create([
                        'client_id' => $lockedClient->id,
                        'user_id' => auth()->id(),
                        'invoice_id' => $invoice->id,
                        'amount' => $dismissAmount,
                        'type' => 'due dismiss',
                        'payment_method' => 'dismiss',
                        'transaction_id' => 'DISMISS-' . $lockedClient->id . '-' . uniqid(),
                        'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                        'updated_at' => $validated['date']
                    ]);
                }
            }

            $finalAmount = max($totalDismissed, $validated['amount']);
            $excessAmount = $finalAmount - $totalDismissed;

            if ($excessAmount > 0) {
                \App\Models\Payment::create([
                    'client_id' => $lockedClient->id,
                    'user_id' => auth()->id(),
                    'amount' => $excessAmount,
                    'type' => 'due dismiss',
                    'payment_method' => 'dismiss',
                    'transaction_id' => 'DISMISS-' . $lockedClient->id . '-' . uniqid(),
                    'created_at' => $validated['date'] === date('Y-m-d') ? now() : \Carbon\Carbon::parse($validated['date'])->endOfDay(),
                    'updated_at' => $validated['date']
                ]);
            }
            if ($finalAmount > 0) {
                $lockedClient->total_due -= $finalAmount;
                $lockedClient->save();
            }
        });

        return response()->json(['message' => 'Invoices dismissed successfully.', 'client' => $client->fresh()]);
    }
}
