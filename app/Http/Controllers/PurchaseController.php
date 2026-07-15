<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'items.product'])->latest('purchase_date');
        
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        return $query->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'paid_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($validated) {
            $supplier = Supplier::lockForUpdate()->find($validated['supplier_id']);
            
            $grandTotal = 0;
            foreach ($validated['items'] as $item) {
                $grandTotal += ($item['quantity'] * $item['unit_cost']);
            }
            
            $paidAmount = min($validated['paid_amount'], $grandTotal);
            $dueAmount = max(0, $grandTotal - $paidAmount);

            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'purchase_date' => $validated['purchase_date'],
                'reference_no' => $validated['reference_no'],
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $item['quantity'] * $item['unit_cost']
                ]);
                
                // Increase stock
                $product = Product::find($item['product_id']);
                $product->increment('stock_quantity', $item['quantity']);
            }

            if ($dueAmount > 0) {
                $supplier->increment('balance', $dueAmount);
            }

            if ($paidAmount > 0) {
                Payment::create([
                    'amount' => $paidAmount,
                    'type' => 'out', // money leaving the business
                    'payment_method' => 'cash',
                    'transaction_id' => 'PUR-' . $purchase->id
                ]);
            }

            return response()->json($purchase->load(['supplier', 'items.product']), 201);
        });
    }

    public function show(Purchase $purchase)
    {
        return $purchase->load(['supplier', 'items.product']);
    }

    public function destroy(Purchase $purchase)
    {
        return response()->json(['message' => 'Purchases cannot be deleted for accounting integrity.'], 422);
    }
}
