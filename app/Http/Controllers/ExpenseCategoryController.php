<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('all')) {
            return ExpenseCategory::orderBy('name')->get();
        }
        return ExpenseCategory::orderBy('name')->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:expense_categories,name|max:255'
        ]);

        $category = ExpenseCategory::create($validated);
        return response()->json($category, 201);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return $expenseCategory;
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id
        ]);

        $expenseCategory->update($validated);
        return response()->json($expenseCategory);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if (\App\Models\Expense::where('expense_category_id', $expenseCategory->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete category because it has associated expenses.'
            ], 422);
        }

        $expenseCategory->delete();
        return response()->json(null, 204);
    }
}
