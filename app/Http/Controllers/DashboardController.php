<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $todayIn = \App\Models\Payment::whereDate('created_at', $today)->where('type', 'in')->sum('amount');
        $todayOut = \App\Models\Payment::whereDate('created_at', $today)->where('type', 'out')->sum('amount');
        $todayRevenue = $todayIn - $todayOut;

        $monthlyIn = \App\Models\Payment::whereBetween('created_at', [$startOfMonth, Carbon::now()])->where('type', 'in')->sum('amount');
        $monthlyOut = \App\Models\Payment::whereBetween('created_at', [$startOfMonth, Carbon::now()])->where('type', 'out')->sum('amount');
        $monthlyRevenue = $monthlyIn - $monthlyOut;

        $totalDue = Client::sum('total_due');

        $activeBookingsCount = Booking::whereIn('status', ['pending', 'confirmed', 'running'])
            ->whereHas('slots', function ($q) use ($today) {
                $q->where('date', '>=', $today->format('Y-m-d'));
            })->count();

        $todayBookings = Booking::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $monthlyBookings = Booking::whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $dueClientsCount = Client::where('total_due', '>', 0)->count();

        return response()->json([
            'today_revenue' => $todayRevenue,
            'today_bookings' => $todayBookings,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_bookings' => $monthlyBookings,
            'total_due' => $totalDue,
            'due_clients_count' => $dueClientsCount,
            'active_bookings' => $activeBookingsCount
        ]);
    }

    public function chart()
    {
        // Get last 7 days accurate Net Revenue (In - Out) from Payments table
        $chartDataIn = \App\Models\Payment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue')
            )
            ->where('type', 'in')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
            
        $chartDataOut = \App\Models\Payment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as refunds')
            )
            ->where('type', 'out')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // Format for Chart.js
        $labels = [];
        $data = [];
        
        // Ensure all 7 days are present even if 0
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('D'); // Mon, Tue
            
            $inData = $chartDataIn->firstWhere('date', $date);
            $outData = $chartDataOut->firstWhere('date', $date);
            
            $grossRevenue = $inData ? (float) $inData->revenue : 0;
            $refunds = $outData ? (float) $outData->refunds : 0;
            
            $data[] = max(0, $grossRevenue - $refunds);
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }
}
