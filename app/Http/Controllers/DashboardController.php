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

        // 1. Sales & Revenue
        $todayPosSales = \App\Models\Invoice::whereDate('created_at', $today)->sum('grand_total');
        $todayBookingSales = \App\Models\Booking::whereDate('created_at', $today)->whereNotIn('status', ['cancelled'])->sum('net_amount');
        $todaySales = $todayPosSales + $todayBookingSales;

        $monthlyPosSales = \App\Models\Invoice::whereBetween('created_at', [$startOfMonth, Carbon::now()])->sum('grand_total');
        $monthlyBookingSales = \App\Models\Booking::whereBetween('created_at', [$startOfMonth, Carbon::now()])->whereNotIn('status', ['cancelled'])->sum('net_amount');
        $monthlySales = $monthlyPosSales + $monthlyBookingSales;
        
        $todayIn = \App\Models\Payment::whereDate('created_at', $today)->where('type', 'in')->sum('amount');
        $todayOut = \App\Models\Payment::whereDate('created_at', $today)->where('type', 'out')->sum('amount');
        $todayRevenue = $todayIn - $todayOut;

        $monthlyIn = \App\Models\Payment::whereBetween('created_at', [$startOfMonth, Carbon::now()])->where('type', 'in')->sum('amount');
        $monthlyOut = \App\Models\Payment::whereBetween('created_at', [$startOfMonth, Carbon::now()])->where('type', 'out')->sum('amount');
        $monthlyRevenue = $monthlyIn - $monthlyOut;

        // 2. Profit & Loss (Sales - Expenses - Purchases)
        $todayExpenses = \App\Models\Expense::whereDate('date', $today)->sum('amount');
        $todayPurchases = \App\Models\Purchase::whereDate('purchase_date', $today)->sum('grand_total');
        $todayProfit = $todaySales - ($todayExpenses + $todayPurchases);

        $monthlyExpenses = \App\Models\Expense::whereBetween('date', [$startOfMonth->format('Y-m-d'), Carbon::now()->format('Y-m-d')])->sum('amount');
        $monthlyPurchases = \App\Models\Purchase::whereBetween('purchase_date', [$startOfMonth->format('Y-m-d'), Carbon::now()->format('Y-m-d')])->sum('grand_total');
        $monthlyProfit = $monthlySales - ($monthlyExpenses + $monthlyPurchases);

        $totalDue = Client::sum('total_due');

        // 3. Booking Status
        $todayConfirmedBookings = Booking::whereHas('slots', function ($q) use ($today) {
            $q->where('date', $today->format('Y-m-d'));
        })->where('status', 'confirmed')->count();

        $todayPendingBookings = Booking::whereHas('slots', function ($q) use ($today) {
            $q->where('date', $today->format('Y-m-d'));
        })->where('status', 'pending')->count();

        $activeBookingsCount = Booking::whereIn('status', ['pending', 'confirmed', 'running'])
            ->whereHas('slots', function ($q) use ($today) {
                $q->where('date', '>=', $today->format('Y-m-d'));
            })->count();

        $dueClientsCount = Client::where('total_due', '>', 0)->count();

        return response()->json([
            'today_sales' => $todaySales,
            'today_profit' => $todayProfit,
            'monthly_sales' => $monthlySales,
            'monthly_profit' => $monthlyProfit,
            'today_revenue' => $todayRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'today_confirmed_bookings' => $todayConfirmedBookings,
            'today_pending_bookings' => $todayPendingBookings,
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
