<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::latest();
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }
        
        if ($request->has('all')) {
            return $query->get();
        }
        return $query->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'balance' => 'numeric|default:0'
        ]);

        $supplier = Supplier::create($validated);
        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier)
    {
        return $supplier;
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
        ]);

        $supplier->update($validated);
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->balance != 0) {
            return response()->json(['message' => 'Cannot delete a supplier with an outstanding or advance balance.'], 422);
        }
        
        try {
            $supplier->delete();
            return response()->json(null, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Cannot delete this supplier because they have associated purchases or transaction history.'
            ], 422);
        }
    }
    
    public function paySupplier(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1' // Removed max limit to allow Advance Payments
        ]);
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $supplier) {
            $supplier = Supplier::lockForUpdate()->find($supplier->id);
            
            $supplier->decrement('balance', $validated['amount']);
            
            \App\Models\Payment::create([
                'amount' => $validated['amount'],
                'type' => 'out', // money leaving the business
                'payment_method' => 'cash',
                'transaction_id' => 'SUP-PAY-' . $supplier->id . '-' . time(),
                'client_id' => null // Optional: Add a supplier_id to Payments table if needed, for now we use transaction_id format
            ]);
            
            return response()->json(['message' => 'Payment recorded successfully', 'supplier' => $supplier]);
        });
    }

    public function receiveRefund(Request $request, Supplier $supplier)
    {
        // When we return goods, the supplier gives us cash back
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $supplier) {
            $supplier = Supplier::lockForUpdate()->find($supplier->id);
            
            // If they give us a refund, they owe us less (or we owe them more).
            // This increases their balance (moves it towards positive/due).
            $supplier->increment('balance', $validated['amount']);
            
            \App\Models\Payment::create([
                'amount' => $validated['amount'],
                'type' => 'in', // money entering the business
                'payment_method' => 'cash',
                'transaction_id' => 'SUP-REF-' . $supplier->id . '-' . time()
            ]);
            
            return response()->json(['message' => 'Refund recorded successfully', 'supplier' => $supplier]);
        });
    }

    public function ledger(Supplier $supplier)
    {
        // Get all purchases
        $purchases = \App\Models\Purchase::where('supplier_id', $supplier->id)
            ->select('id', 'purchase_date as date', 'reference_no', 'grand_total as amount', \Illuminate\Support\Facades\DB::raw("'Purchase' as type"), 'paid_amount', 'due_amount')
            ->get();
            
        // Get all payments (We identify supplier payments by transaction_id prefix)
        $payments = \App\Models\Payment::where('transaction_id', 'like', "SUP-PAY-{$supplier->id}-%")
            ->select('id', 'created_at as date', 'transaction_id as reference_no', 'amount', \Illuminate\Support\Facades\DB::raw("'Payment Made' as type"))
            ->get();
            
        // Get all refunds
        $refunds = \App\Models\Payment::where('transaction_id', 'like', "SUP-REF-{$supplier->id}-%")
            ->select('id', 'created_at as date', 'transaction_id as reference_no', 'amount', \Illuminate\Support\Facades\DB::raw("'Refund Received' as type"))
            ->get();

        $ledger = collect($purchases)
            ->concat($payments)
            ->concat($refunds)
            ->sortByDesc('date')
            ->values();

        return response()->json([
            'supplier' => $supplier,
            'ledger' => $ledger
        ]);
    }
}
