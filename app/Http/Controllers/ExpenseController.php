<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('category')->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }
        
        return $query->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date_format:Y-m-d',
            'description' => 'nullable|string'
        ]);

        $expense = DB::transaction(function () use ($validated) {
            $exp = Expense::create($validated);
            
            // Log as outgoing payment for accounting
            \App\Models\Payment::create([
                'amount' => $validated['amount'],
                'type' => 'out',
                'payment_method' => 'cash', // Assuming expenses are paid in cash for now
                'transaction_id' => 'EXP-' . $exp->id,
                'created_at' => $validated['date'] . ' ' . date('H:i:s')
            ]);
            
            return $exp;
        });

        return response()->json($expense->load('category'), 201);
    }

    public function show(Expense $expense)
    {
        return $expense->load('category');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date_format:Y-m-d',
            'description' => 'nullable|string'
        ]);
        
        return DB::transaction(function () use ($validated, $expense) {
            $expense->update($validated);
            
            // Sync with Payment ledger
            $payment = \App\Models\Payment::where('transaction_id', 'EXP-' . $expense->id)->first();
            if ($payment) {
                $payment->update([
                    'amount' => $validated['amount'],
                    'created_at' => $validated['date'] . ' ' . date('H:i:s')
                ]);
            }
            
            return response()->json($expense->load('category'));
        });
    }

    public function destroy(Expense $expense)
    {
        return DB::transaction(function () use ($expense) {
            // Remove from Payment ledger
            \App\Models\Payment::where('transaction_id', 'EXP-' . $expense->id)->delete();
            
            $expense->delete();
            return response()->json(null, 204);
        });
    }
}
