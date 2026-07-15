<?php

namespace App\Http\Controllers;

use App\Models\MonthlyClosing;
use Illuminate\Http\Request;

class MonthlyClosingController extends Controller
{
    public function index()
    {
        return MonthlyClosing::with('closer')->orderBy('month_year', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'month_year' => 'required|date_format:Y-m'
        ]);
        
        $exists = MonthlyClosing::where('month_year', $validated['month_year'])->exists();
        if ($exists) {
            return response()->json(['message' => 'This month is already closed.'], 422);
        }

        $closing = MonthlyClosing::create([
            'month_year' => $validated['month_year'],
            'closed_by' => $request->user()->id
        ]);

        return response()->json($closing->load('closer'), 201);
    }
    
    public function check(Request $request)
    {
        $date = $request->query('date'); // Format: YYYY-MM-DD
        if (!$date) return response()->json(['closed' => false]);
        
        $monthYear = substr($date, 0, 7);
        $closed = MonthlyClosing::where('month_year', $monthYear)->exists();
        
        return response()->json(['closed' => $closed]);
    }
}
