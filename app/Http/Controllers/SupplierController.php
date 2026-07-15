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
        if ($supplier->balance > 0) {
            return response()->json(['message' => 'Cannot delete a supplier with an outstanding balance.'], 422);
        }
        $supplier->delete();
        return response()->json(null, 204);
    }
    
    public function paySupplier(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $supplier->balance
        ]);
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $supplier) {
            $supplier = Supplier::lockForUpdate()->find($supplier->id);
            
            $supplier->decrement('balance', $validated['amount']);
            
            \App\Models\Payment::create([
                'amount' => $validated['amount'],
                'type' => 'out', // money leaving the business
                'payment_method' => 'cash',
                'transaction_id' => 'SUP-PAY-' . $supplier->id . '-' . time()
            ]);
            
            return response()->json(['message' => 'Payment recorded successfully', 'supplier' => $supplier]);
        });
    }
}
