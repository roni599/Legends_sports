<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientTransactionController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:due_receive,due_paid,advance,due_dismiss'
        ]);

        $query = Payment::with(['client:id,name', 'user:id,name', 'invoice:id,invoice_number'])
            ->where('amount', '>', 0)
            ->latest();

        if ($validated['type'] === 'due_receive') {
            $query->where('type', 'due receive');
        } elseif ($validated['type'] === 'due_paid') {
            $query->whereIn('type', ['due pay', 'out']);
        } elseif ($validated['type'] === 'advance') {
            $query->whereIn('type', ['advance receive', 'advance refund']);
        } elseif ($validated['type'] === 'due_dismiss') {
            $query->where('type', 'due dismiss');
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('transaction_id', 'like', "%{$search}%");
        }

        $transactions = $query->paginate(15);
        return response()->json($transactions);
    }

    public function show(Payment $payment)
    {
        $payment->load(['client', 'user', 'invoice']);
        return response()->json($payment);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        $oldAmount = $payment->amount;
        $newAmount = $validated['amount'];
        $difference = $newAmount - $oldAmount;

        DB::transaction(function () use ($payment, $difference, $newAmount) {
            $client = \App\Models\Client::lockForUpdate()->find($payment->client_id);
            if (!$client) {
                return;
            }

            // Based on type, adjust total_due
            if ($payment->type === 'due receive') {
                $client->total_due -= $difference;
            } elseif ($payment->type === 'due pay') {
                $client->total_due += $difference;
            } elseif ($payment->type === 'advance receive') {
                $client->total_due -= $difference;
            } elseif ($payment->type === 'advance refund') {
                $client->total_due += $difference;
            } elseif ($payment->type === 'due dismiss') {
                $client->total_due -= $difference;
            }

            $client->save();
            $payment->amount = $newAmount;
            $payment->save();
            
            if ($payment->invoice_id) {
                $invoice = \App\Models\Invoice::lockForUpdate()->find($payment->invoice_id);
                if ($invoice) {
                    if (in_array($payment->type, ['due receive', 'due dismiss'])) {
                        $invoice->due -= $difference;
                        if (in_array($payment->type, ['due receive'])) {
                            $invoice->paid += $difference;
                        }
                        $invoice->save();
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Transaction updated successfully',
            'payment' => $payment->fresh(['client', 'user', 'invoice'])
        ]);
    }
}
