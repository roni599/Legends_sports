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

        $todayRevenue = Booking::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->sum('paid_amount');

        $monthlyRevenue = Booking::whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->whereNotIn('status', ['cancelled'])
            ->sum('paid_amount');

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
        // Get last 7 days revenue
        $chartData = Booking::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(paid_amount) as revenue')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->whereNotIn('status', ['cancelled'])
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
            
            $dayData = $chartData->firstWhere('date', $date);
            $data[] = $dayData ? (float) $dayData->revenue : 0;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }
}
