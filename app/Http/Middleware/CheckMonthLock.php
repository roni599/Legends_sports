<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMonthLock
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for GET requests
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Check if date or created_at exists in request
        $date = $request->input('date') ?? date('Y-m-d');
        $monthYear = substr($date, 0, 7); // YYYY-MM

        $isLocked = \App\Models\MonthlyClosing::where('month_year', $monthYear)->exists();

        if ($isLocked) {
            return response()->json([
                'message' => "Accounting for {$monthYear} is locked. You cannot add, modify, or delete records in a locked month."
            ], 423); // 423 Locked
        }

        return $next($request);
    }
}
