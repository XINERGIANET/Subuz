<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\CashboxMovement;

class WebController extends Controller
{
    public function index(){
        if(auth()->check() && auth()->user()->hasRole('despachador')){
            return redirect()->route('sales.index');
        }
        $products = Product::all();
        return view('index', compact('products'));
    }

    public function reports(){
        return view('reports');
    }

    public function dashboard(Request $request){
        $isAssistant = auth()->user()->hasRole('asistente');
        
        // Use consistent date handling
        $today = now()->toDateString();
        $start_date = $isAssistant ? $today : $request->start_date;
        $end_date = $isAssistant ? $today : $request->end_date;
        $period = $isAssistant ? 'day' : $request->get('period');

        $query = Sale::query();
        
        if ($start_date) {
            $query->whereDate('date', '>=', $start_date);
        }
        if ($end_date) {
            $query->whereDate('date', '<=', $end_date);
        }

        $sales = (clone $query)->sum('total');

        $expenses = Expense::when($start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');

        $manual_income = CashboxMovement::where('type', 'income')
            ->when($start_date, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($end_date, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->sum('amount');

        $revenues = $sales - $expenses;
        $pending = (clone $query)->where('paid', 0)->sum('total');

        // Total Entregado logic (matching SaleController)
        $total_sales_paid_query = (clone $query)
            ->where('status', '!=', 'Anulado')
            ->where(function($q) {
                $q->where('paid', 1)
                ->orWhere('type', 'Pago pendiente')
                ->orWhereHas('movements', function($mq) {
                    $mq->where('type', 'debt');
                });
            });
        
        $total_sales_paid = $total_sales_paid_query->sum('total');

        $total_credit = (clone $query)
            ->where('status', '!=', 'Anulado')
            ->where('type', 'Credito')
            ->sum('total');

        $global_payments = Payment::when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
            ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
            ->sum('amount');
        
        $global_manual_income = CashboxMovement::where('type', 'income')
            ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
            ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
            ->sum('amount');
            
        $global_expense = Expense::when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
            ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
            ->sum('amount');
            
        $total_balance = ($global_payments + $global_manual_income) - $global_expense;
        
        $payment_methods_data = PaymentMethod::all();
        $methods_totals = [];
        
        foreach($payment_methods_data as $method) {
            $payments = Payment::where('payment_method_id', $method->id)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->sum('amount');
            $manual = CashboxMovement::where('type', 'income')->where('payment_method_id', $method->id)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->sum('amount');
            $expense = Expense::where('payment_method_id', $method->id)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->sum('amount');
            $balance = ($payments + $manual) - $expense;

            $methods_totals[] = [
                'name' => $method->name,
                'total' => number_format($balance, 2)
            ];
        }
        
        $chartStart = $start_date ?: now()->startOfYear()->toDateString();
        $chartEnd = $end_date ?: now()->endOfYear()->toDateString();
        $chart = $this->dashboardChartPeriods($chartStart, $chartEnd, $period);

        $totalSalesByPeriod = [];
        $totalExpensesByPeriod = [];
        $totalManualIncomeByPeriod = [];

        foreach($chart['ranges'] as $range){
            $totalSalesByPeriod[] = (float) Sale::whereDate('date', '>=', $range['start'])
                ->whereDate('date', '<=', $range['end'])
                ->sum('total');

            $totalExpensesByPeriod[] = (float) Expense::whereDate('date', '>=', $range['start'])
                ->whereDate('date', '<=', $range['end'])
                ->sum('amount');

            $totalManualIncomeByPeriod[] = (float) CashboxMovement::where('type', 'income')
                ->whereDate('date', '>=', $range['start'])
                ->whereDate('date', '<=', $range['end'])
                ->sum('amount');
        }

        $total_sales_paid = Sale::when($isAssistant, function($q){ return $q->whereDate('date', now()); })
            ->where('status', '!=', 'Anulado')
            ->where('paid', 1)
            ->sum('total');
        
        $total_credit = Sale::when($isAssistant, function($q){ return $q->whereDate('date', now()); })
            ->where('status', '!=', 'Anulado')
            ->where('type', 'Credito')
            ->sum('total');

        return response()->json([
            'sales' => number_format($sales, 2),
            'expenses' => number_format($expenses, 2),
            'manual_income' => number_format($manual_income, 2),
            'revenues' => number_format($revenues, 2),
            'pending' => number_format($pending, 2),
            'total_balance' => number_format($total_balance, 2),
            'total_sales_paid' => number_format($total_sales_paid, 2),
            'total_credit' => number_format($total_credit, 2),
            'methods' => $methods_totals,
            'period' => $chart['period'],
            'chartLabels' => $chart['labels'],
            'totalSales' => $totalSalesByPeriod,
            'totalExpenses' => $totalExpensesByPeriod,
            'totalManualIncome' => $totalManualIncomeByPeriod
        ]);
    }

    private function dashboardChartPeriods($startDate, $endDate, $period = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if (!in_array($period, ['year', 'month', 'day'])) {
            if ($start->toDateString() === $end->toDateString()) {
                $period = 'day';
            } elseif ($start->format('Y-m') === $end->format('Y-m')) {
                $period = 'month';
            } else {
                $period = 'year';
            }
        }

        if ($period === 'day') {
            return [
                'period' => 'day',
                'labels' => [$start->format('d/m')],
                'ranges' => [[
                    'start' => $start->toDateString(),
                    'end' => $start->toDateString(),
                ]],
            ];
        }

        if ($period === 'month') {
            $labels = [];
            $ranges = [];
            $cursor = $start->copy();
            $week = 1;

            while ($cursor->lte($end)) {
                $rangeStart = $cursor->copy();
                $rangeEnd = $cursor->copy()->addDays(6);

                if ($rangeEnd->gt($end)) {
                    $rangeEnd = $end->copy();
                }

                $labels[] = 'Sem ' . $week . ' (' . $rangeStart->day . '-' . $rangeEnd->day . ')';
                $ranges[] = [
                    'start' => $rangeStart->toDateString(),
                    'end' => $rangeEnd->toDateString(),
                ];

                $cursor = $rangeEnd->copy()->addDay();
                $week++;
            }

            return [
                'period' => 'month',
                'labels' => $labels,
                'ranges' => $ranges,
            ];
        }

        $labels = [];
        $ranges = [];
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $rangeStart = $cursor->copy()->startOfMonth();
            $rangeEnd = $cursor->copy()->endOfMonth();

            if ($rangeStart->lt($start)) {
                $rangeStart = $start->copy();
            }

            if ($rangeEnd->gt($end)) {
                $rangeEnd = $end->copy();
            }

            $labels[] = $monthNames[$cursor->month - 1];
            $ranges[] = [
                'start' => $rangeStart->toDateString(),
                'end' => $rangeEnd->toDateString(),
            ];

            $cursor->addMonthNoOverflow();
        }

        return [
            'period' => 'year',
            'labels' => $labels,
            'ranges' => $ranges,
        ];
    }

    public function dashboardProduct(Request $request){
        $baseQuery = DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sale_details.product_id', $request->product_id);

        $salesYear = (clone $baseQuery)
            ->when($request->year, function($query, $year){
                return $query->whereYear('sales.date', $year);
            })->sum(DB::raw('sale_details.price * sale_details.quantity'));

        $salesMonth = (clone $baseQuery)
            ->when($request->year, function($query, $year){
                return $query->whereYear('sales.date', $year);
            })->when($request->month, function($query, $month){
                return $query->whereMonth('sales.date', $month);
            })->sum(DB::raw('sale_details.price * sale_details.quantity'));

        $salesByMonth = (clone $baseQuery)
            ->select(
                DB::raw('MONTH(sales.date) as month'),
                DB::raw('SUM(sale_details.price * sale_details.quantity) as total'),
            )
            ->when($request->year, function($query, $year){
                return $query->whereYear('sales.date', $year);
            })->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $chartSalesMonth = [0,0,0,0,0,0,0,0,0,0,0,0];
        foreach($salesByMonth as $sale){
            $chartSalesMonth[$sale->month-1] = $sale->total;
        }

        return response()->json([
            'sales_year' => number_format($salesYear, 2),
            'sales_month' => number_format($salesMonth, 2),
            'chart_sales_month' => $chartSalesMonth
        ]);
    }

    public function dashboardDistribution(Request $request){
        $distribution = DB::table('sale_details')
            ->select('products.name', DB::raw('SUM(sale_details.price * sale_details.quantity) as total'))
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->when($request->year, function($query, $year){
                return $query->whereYear('sales.date', $year);
            })
            ->when($request->month, function($query, $month){
                return $query->whereMonth('sales.date', $month);
            })
            ->groupBy('products.id', 'products.name')
            ->get();

        return response()->json([
            'distribution' => $distribution
        ]);
    }
    public function dashboardDaily(Request $request){
        $date = $request->date ?? date('Y-m-d');

        // Total sales/orders for the day. A sale can contain several products.
        $sold = DB::table('sales')
            ->whereDate('sales.date', $date)
            ->where('sales.status', '!=', 'Anulado')
            ->count();

        // Total dispatched sales/orders for the day, using the same delivered logic as SaleController.
        $dispatched = DB::table('sales')
            ->whereDate('sales.date', $date)
            ->where('sales.status', '!=', 'Anulado')
            ->where(function ($q) {
                $q->where('sales.paid', 1)
                    ->orWhere('sales.type', 'Pago pendiente')
                    ->orWhereExists(function ($subquery) {
                        $subquery->select(DB::raw(1))
                            ->from('cashbox_movements')
                            ->whereColumn('cashbox_movements.sale_id', 'sales.id')
                            ->where('cashbox_movements.type', 'debt');
                    });
            })
            ->count();

        return response()->json([
            'sold' => (int)$sold,
            'dispatched' => (int)$dispatched
        ]);
    }
}
