<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Atik\Pdf\Facades\AtikPdf;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Purchase;

class ReportController extends Controller
{
    public function cashFlow(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');

        $payments = Payment::with('client')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        $rows = [];
        $totalIn = 0;
        $totalOut = 0;

        foreach ($payments as $payment) {
            $rows[] = [
                'Date' => $payment->created_at->format('Y-m-d H:i'),
                'Transaction ID' => $payment->transaction_id,
                'Type' => strtoupper($payment->type),
                'Method' => ucfirst($payment->payment_method),
                'Amount (In)' => $payment->type === 'in' ? $payment->amount : 0,
                'Amount (Out)' => $payment->type === 'out' ? $payment->amount : 0,
            ];
            if ($payment->type === 'in') $totalIn += $payment->amount;
            if ($payment->type === 'out') $totalOut += $payment->amount;
        }

        // Add summary row
        $rows[] = [
            'Date' => 'TOTAL SUMMARY',
            'Transaction ID' => '',
            'Type' => '',
            'Method' => 'Net: ' . ($totalIn - $totalOut),
            'Amount (In)' => $totalIn,
            'Amount (Out)' => $totalOut,
        ];

        $columns = ['Date', 'Transaction ID', 'Type', 'Method', 'Amount (In)', 'Amount (Out)'];
        $title = "Cash Flow Report ($start to $end)";

        if ($format === 'json') {
            return response()->json(['data' => $rows, 'start' => $start, 'end' => $end, 'total_in' => $totalIn, 'total_out' => $totalOut]);
        }

        $pdf = AtikPdf::table($rows, $columns, $title);
        
        $filename = 'cash_flow_' . time();
        if ($format === 'excel') {
            return $pdf->excel()->download($filename);
        }

        return $pdf->download($filename);
    }

    public function incomeExpense(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');

        $rows = [];
        $totalIncome = 0;
        $totalExpense = 0;

        // 1. POS Sales (Invoices)
        $invoices = Invoice::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->get();
        foreach ($invoices as $inv) {
            $rows[] = [
                'Date' => $inv->created_at->format('Y-m-d'),
                'Category' => 'POS Sale',
                'Reference' => $inv->invoice_number,
                'Income (৳)' => $inv->grand_total,
                'Expense (৳)' => 0
            ];
            $totalIncome += $inv->grand_total;
        }

        // 2. Bookings
        $bookings = Booking::whereNotIn('status', ['cancelled'])
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->get();
        foreach ($bookings as $bkg) {
            $rows[] = [
                'Date' => $bkg->created_at->format('Y-m-d'),
                'Category' => 'Ground Booking',
                'Reference' => 'BKG-' . $bkg->id,
                'Income (৳)' => $bkg->net_amount,
                'Expense (৳)' => 0
            ];
            $totalIncome += $bkg->net_amount;
        }

        // 3. Expenses
        $expenses = Expense::with('category')->whereBetween('date', [$start, $end])->get();
        foreach ($expenses as $exp) {
            $rows[] = [
                'Date' => $exp->date,
                'Category' => 'Operating Expense (' . ($exp->category ? $exp->category->name : 'N/A') . ')',
                'Reference' => $exp->description ?? 'EXP-' . $exp->id,
                'Income (৳)' => 0,
                'Expense (৳)' => $exp->amount
            ];
            $totalExpense += $exp->amount;
        }

        // 4. Purchases
        $purchases = Purchase::whereBetween('purchase_date', [$start, $end])->get();
        foreach ($purchases as $pur) {
            $rows[] = [
                'Date' => $pur->purchase_date,
                'Category' => 'Stock Purchase',
                'Reference' => $pur->reference_no ?? 'PUR-' . $pur->id,
                'Income (৳)' => 0,
                'Expense (৳)' => $pur->grand_total
            ];
            $totalExpense += $pur->grand_total;
        }

        // Sort rows by Date
        usort($rows, function ($a, $b) {
            return strtotime($a['Date']) - strtotime($b['Date']);
        });

        // Add summary row
        $rows[] = [
            'Date' => 'TOTAL SUMMARY',
            'Category' => '',
            'Reference' => 'Net Profit: ' . ($totalIncome - $totalExpense),
            'Income (৳)' => $totalIncome,
            'Expense (৳)' => $totalExpense
        ];

        $columns = ['Date', 'Category', 'Reference', 'Income (৳)', 'Expense (৳)'];
        $title = "Income vs Expense Report ($start to $end)";

        if ($format === 'json') {
            return response()->json(['data' => $rows, 'start' => $start, 'end' => $end, 'total_income' => $totalIncome, 'total_expense' => $totalExpense]);
        }

        $pdf = AtikPdf::table($rows, $columns, $title);
        
        $filename = 'income_expense_' . time();
        if ($format === 'excel') {
            return $pdf->excel()->download($filename);
        }

        return $pdf->download($filename);
    }

    public function genericReport(Request $request, $type)
    {
        $format = $request->input('format', 'json');
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        
        $rows = [];
        $columns = [];
        $title = ucwords(str_replace('-', ' ', $type)) . ' Report';

        switch ($type) {
            case 'income-vs-expense':
                $incomesQuery = \App\Models\Booking::where('status', 'completed');
                $expensesQuery = \App\Models\Expense::query();
                $purchasesQuery = \App\Models\Purchase::query();

                if ($request->has('start_date') && $request->has('end_date')) {
                    $incomesQuery->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                    $expensesQuery->whereBetween('expense_date', [$request->start_date, $request->end_date]);
                    $purchasesQuery->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
                }

                $incomes = $incomesQuery->get();
                $expenses = $expensesQuery->get();
                $purchases = $purchasesQuery->get();

                // Group by date
                $dailyData = [];

                foreach ($incomes as $inc) {
                    $date = $inc->created_at->format('Y-m-d');
                    if (!isset($dailyData[$date])) $dailyData[$date] = ['income' => 0, 'expense' => 0];
                    $dailyData[$date]['income'] += $inc->net_amount;
                }

                foreach ($expenses as $exp) {
                    $date = \Carbon\Carbon::parse($exp->expense_date)->format('Y-m-d');
                    if (!isset($dailyData[$date])) $dailyData[$date] = ['income' => 0, 'expense' => 0];
                    $dailyData[$date]['expense'] += $exp->amount;
                }

                foreach ($purchases as $pur) {
                    $date = \Carbon\Carbon::parse($pur->purchase_date)->format('Y-m-d');
                    if (!isset($dailyData[$date])) $dailyData[$date] = ['income' => 0, 'expense' => 0];
                    $dailyData[$date]['expense'] += $pur->grand_total;
                }

                // Sort by date
                ksort($dailyData);

                $columns = ['Date', 'Total Income (৳)', 'Total Expense (৳)', 'Profit / Loss (৳)'];
                
                $totalInc = 0;
                $totalExp = 0;
                
                foreach ($dailyData as $date => $data) {
                    $profit = $data['income'] - $data['expense'];
                    $totalInc += $data['income'];
                    $totalExp += $data['expense'];
                    
                    $rows[] = [
                        'Date' => $date,
                        'Total Income (৳)' => $data['income'],
                        'Total Expense (৳)' => $data['expense'],
                        'Profit / Loss (৳)' => $profit
                    ];
                }

                if (count($rows) > 0) {
                    $rows[] = [
                        'Date' => 'TOTAL',
                        'Total Income (৳)' => $totalInc,
                        'Total Expense (৳)' => $totalExp,
                        'Profit / Loss (৳)' => $totalInc - $totalExp
                    ];
                }
                break;
            case 'suppliers':
                $suppliers = \App\Models\Supplier::all();
                $columns = ['Supplier Info', 'Company', 'Address', 'Total Bought (৳)', 'Total Paid (৳)', 'Due (৳)', 'Advance (৳)'];
                foreach ($suppliers as $sup) {
                    $purchases = \App\Models\Purchase::where('supplier_id', $sup->id)->get();
                    $totalBought = $purchases->sum('grand_total');
                    $totalPaid = $purchases->sum('paid_amount');
                    $due = $sup->balance > 0 ? $sup->balance : 0;
                    $advance = $sup->balance < 0 ? abs($sup->balance) : 0;
                    
                    $rows[] = [
                        'Supplier Info' => $sup->name . ($sup->phone ? "\n" . $sup->phone : ''),
                        'Company' => $sup->company ?? 'N/A',
                        'Address' => $sup->address ?? 'N/A',
                        'Total Bought (৳)' => $totalBought,
                        'Total Paid (৳)' => $totalPaid,
                        'Due (৳)' => $due,
                        'Advance (৳)' => $advance
                    ];
                }
                break;
            case 'clients':
                $clients = \App\Models\Client::all();
                $columns = ['ID', 'Name', 'Phone', 'Email', 'Address', 'Total Due (৳)', 'Joined Date'];
                foreach ($clients as $cli) {
                    $rows[] = [
                        'ID' => 'CLI-' . $cli->id,
                        'Name' => $cli->name,
                        'Phone' => $cli->phone,
                        'Email' => $cli->email ?? 'N/A',
                        'Address' => $cli->address ?? 'N/A',
                        'Total Due (৳)' => $cli->total_due,
                        'Joined Date' => $cli->created_at->format('Y-m-d')
                    ];
                }
                break;
            case 'client-ground':
                $clients = \App\Models\Client::with(['bookings.ground', 'bookings.slots'])->get();
                $columns = ['Name', 'Phone', 'Email', 'Address', 'Booked Slots', 'Play Time', 'Total Billed (৳)', 'Total Paid (৳)', 'Due (৳)', 'Advance (৳)'];
                foreach ($clients as $cli) {
                    $totalPlays = 0;
                    $grounds = [];
                    $slotDetails = [];
                    $totalBilled = 0;
                    $totalPaid = 0;
                    foreach ($cli->bookings as $bkg) {
                        if ($bkg->status !== 'cancelled') {
                            $totalPlays += $bkg->slots->count();
                            $groundName = $bkg->ground ? $bkg->ground->name : 'Unknown';
                            $grounds[$groundName] = true;
                            
                            foreach ($bkg->slots as $slot) {
                                $slotDetails[] = $groundName . ' - ' . date('h:i A', strtotime($slot->time_start)) . ' (৳' . $slot->price . ')';
                            }
                            $totalBilled += $bkg->net_amount;
                            $totalPaid += $bkg->paid_amount;
                        }
                    }
                    
                    $displaySlots = count($slotDetails) > 5 ? implode("\n", array_slice($slotDetails, 0, 5)) . "\n...and " . (count($slotDetails) - 5) . " more" : implode("\n", $slotDetails);
                    
                    $due = $cli->total_due > 0 ? $cli->total_due : 0;
                    $advance = $cli->total_due < 0 ? abs($cli->total_due) : 0;
                    
                    $rows[] = [
                        'Name' => $cli->name,
                        'Phone' => $cli->phone,
                        'Email' => $cli->email ?? 'N/A',
                        'Address' => $cli->address ?? 'N/A',
                        'Booked Slots' => $displaySlots ?: 'No Plays',
                        'Play Time' => $totalPlays . ' times',
                        'Total Billed (৳)' => $totalBilled,
                        'Total Paid (৳)' => $totalPaid,
                        'Due (৳)' => $due,
                        'Advance (৳)' => $advance
                    ];
                }
                break;
            case 'products':
                $products = \App\Models\Product::with('category')->get();
                $columns = ['Barcode', 'Name', 'Category', 'Price (৳)', 'Status'];
                foreach ($products as $prod) {
                    $rows[] = [
                        'Barcode' => $prod->barcode,
                        'Name' => $prod->name,
                        'Category' => $prod->category ? $prod->category->name : 'N/A',
                        'Price (৳)' => $prod->price,
                        'Status' => $prod->is_active ? 'Active' : 'Inactive'
                    ];
                }
                break;
            case 'stock':
                $products = \App\Models\Product::with('category')->get();
                $columns = ['Barcode', 'Name', 'Category', 'Current Stock', 'Stock Value (৳)'];
                $totalVal = 0;
                foreach ($products as $prod) {
                    $val = $prod->stock * $prod->price;
                    $rows[] = [
                        'Barcode' => $prod->barcode,
                        'Name' => $prod->name,
                        'Category' => $prod->category ? $prod->category->name : 'N/A',
                        'Current Stock' => $prod->stock,
                        'Stock Value (৳)' => $val
                    ];
                    $totalVal += $val;
                }
                $rows[] = [
                    'Barcode' => 'TOTAL',
                    'Name' => '',
                    'Category' => '',
                    'Current Stock' => '',
                    'Stock Value (৳)' => $totalVal
                ];
                break;
            case 'income':
                $columns = ['Date', 'Category', 'Reference', 'Amount (৳)'];
                $totalIncome = 0;
                $invoices = \App\Models\Invoice::when($start && $end, function($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                })->get();
                foreach ($invoices as $inv) {
                    $rows[] = ['Date' => $inv->created_at->format('Y-m-d'), 'Category' => 'POS Sale', 'Reference' => $inv->invoice_number, 'Amount (৳)' => $inv->grand_total];
                    $totalIncome += $inv->grand_total;
                }
                $bookings = \App\Models\Booking::whereNotIn('status', ['cancelled'])->when($start && $end, function($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                })->get();
                foreach ($bookings as $bkg) {
                    $rows[] = ['Date' => $bkg->created_at->format('Y-m-d'), 'Category' => 'Ground Booking', 'Reference' => 'BKG-'.$bkg->id, 'Amount (৳)' => $bkg->net_amount];
                    $totalIncome += $bkg->net_amount;
                }
                usort($rows, function ($a, $b) { return strtotime($a['Date']) - strtotime($b['Date']); });
                $rows[] = ['Date' => 'TOTAL', 'Category' => '', 'Reference' => '', 'Amount (৳)' => $totalIncome];
                break;
            case 'expense':
                $columns = ['Date', 'Category', 'Reference', 'Amount (৳)'];
                $totalExpense = 0;
                $expenses = \App\Models\Expense::with('category')->when($start && $end, function($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                })->get();
                foreach ($expenses as $exp) {
                    $rows[] = ['Date' => $exp->date, 'Category' => 'Op Expense ('.($exp->category->name ?? 'N/A').')', 'Reference' => $exp->description ?? 'EXP-'.$exp->id, 'Amount (৳)' => $exp->amount];
                    $totalExpense += $exp->amount;
                }
                $purchases = \App\Models\Purchase::when($start && $end, function($q) use ($start, $end) {
                    $q->whereBetween('purchase_date', [$start, $end]);
                })->get();
                foreach ($purchases as $pur) {
                    $rows[] = ['Date' => $pur->purchase_date, 'Category' => 'Stock Purchase', 'Reference' => $pur->reference_no ?? 'PUR-'.$pur->id, 'Amount (৳)' => $pur->grand_total];
                    $totalExpense += $pur->grand_total;
                }
                usort($rows, function ($a, $b) { return strtotime($a['Date']) - strtotime($b['Date']); });
                $rows[] = ['Date' => 'TOTAL', 'Category' => '', 'Reference' => '', 'Amount (৳)' => $totalExpense];
                break;
            case 'grounds':
                $grounds = \App\Models\Ground::all();
                $columns = ['Name', 'Location', 'Type', 'Status'];
                foreach ($grounds as $grd) {
                    $rows[] = [
                        'Name' => $grd->name,
                        'Location' => $grd->location,
                        'Type' => $grd->type,
                        'Status' => $grd->status
                    ];
                }
                break;
            case 'sales':
                $query = \App\Models\Invoice::with('client');
                if ($start && $end) {
                    $query->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                }
                $sales = $query->get();
                $columns = ['Date', 'Invoice No', 'Client', 'Subtotal (৳)', 'Discount (৳)', 'Total (৳)'];
                $totalSales = 0;
                foreach ($sales as $sale) {
                    $rows[] = [
                        'Date' => $sale->created_at->format('Y-m-d'),
                        'Invoice No' => $sale->invoice_number,
                        'Client' => $sale->client ? $sale->client->name : 'Guest',
                        'Subtotal (৳)' => $sale->subtotal,
                        'Discount (৳)' => $sale->discount,
                        'Total (৳)' => $sale->grand_total
                    ];
                    $totalSales += $sale->grand_total;
                }
                $rows[] = [
                    'Date' => 'TOTAL',
                    'Invoice No' => '',
                    'Client' => '',
                    'Subtotal (৳)' => '',
                    'Discount (৳)' => '',
                    'Total (৳)' => $totalSales
                ];
                break;
            default:
                abort(404, 'Report type not found');
        }

        if ($format === 'json') {
            return response()->json(['columns' => $columns, 'data' => $rows, 'title' => $title]);
        }

        $pdf = AtikPdf::table($rows, $columns, $title);
        $filename = $type . '_report_' . time();

        if ($format === 'excel') {
            return $pdf->excel()->download($filename);
        }

        return $pdf->download($filename);
    }
}
