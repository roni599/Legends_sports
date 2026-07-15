<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.price' => 'required|numeric|min:0',
            'discount' => 'numeric|default:0',
            'paid' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($validated) {
            $subtotal = 0;
            
            // Validate stock and calculate subtotal
            foreach ($validated['cart'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                $subtotal += ($item['quantity'] * $item['price']);
            }
            
            $discount = $validated['discount'] ?? 0;
            $grandTotal = max(0, $subtotal - $discount);
            $paid = $validated['paid'];
            
            if ($paid < $grandTotal) {
                throw new \Exception("Walk-in POS sales do not allow credit. Paid amount (৳{$paid}) must be at least the Grand Total (৳{$grandTotal}).");
            }
            
            $due = 0;
            
            // Create Invoice
            $invoice = Invoice::create([
                'invoice_number' => 'POS-' . strtoupper(uniqid()),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'paid' => $paid,
                'due' => $due
            ]);
            
            // Create Sales and Decrement Stock
            foreach ($validated['cart'] as $item) {
                $product = Product::find($item['product_id']);
                
                Sale::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $product->category,
                    'item_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price']
                ]);
                
                $product->decrement('stock_quantity', $item['quantity']);
            }
            
            // Log Payment (Cash Drawer)
            $actualReceived = min($paid, $grandTotal); // If paid 500 for a 100 bill, revenue is 100, not 500
            
            if ($actualReceived > 0) {
                \App\Models\Payment::create([
                    'amount' => $actualReceived,
                    'type' => 'in',
                    'payment_method' => 'cash',
                    'transaction_id' => $invoice->invoice_number
                ]);
            }
            
            return response()->json([
                'message' => 'Checkout successful',
                'invoice' => $invoice->load('sales')
            ]);
        });
    }
}
