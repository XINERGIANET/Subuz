<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentMethod;

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
        $sales = Sale::when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('total');
        $expenses = Expense::when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');
        $revenues = $sales - $expenses;
        $pending = Sale::when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->where('paid', 0)->sum('total');

        $payments_query = Payment::when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        });

        $global_income = Payment::sum('amount');
        $global_expense = Expense::sum('amount');
        $total_balance = $global_income - $global_expense;
        
        $payment_methods_data = PaymentMethod::all();
        $methods_totals = [];
        
        foreach($payment_methods_data as $method) {
            $income = Payment::where('payment_method_id', $method->id)->sum('amount');
            $expense = Expense::where('payment_method_id', $method->id)->sum('amount');
            $balance = $income - $expense;

            $methods_totals[] = [
                'name' => $method->name,
                'total' => number_format($balance, 2)
            ];
        }
        
        $chart = [];
        
        $salesByMonth = Sale::select(
            DB::raw('MONTH(date) as month'),
            DB::raw('SUM(total) as total'),
        )->whereYear('date', date('Y'))->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $expensesByMonth = Expense::select(
            DB::raw('MONTH(date) as month'),
            DB::raw('SUM(amount) as total'),
        )->whereYear('date', date('Y'))->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $totalSalesByMonth = [0,0,0,0,0,0,0,0,0,0,0,0];
        
        $totalExpensesByMonth = [0,0,0,0,0,0,0,0,0,0,0,0];

        foreach($salesByMonth as $sale){
            $totalSalesByMonth[$sale->month-1] = $sale->total;
        }

        foreach($expensesByMonth as $expense){
            $totalExpensesByMonth[$expense->month-1] = $expense->total;
        }

        return response()->json([
            'sales' => number_format($sales, 2),
            'expenses' => number_format($expenses, 2),
            'revenues' => number_format($revenues, 2),
            'pending' => number_format($pending, 2),
            'total_balance' => number_format($total_balance, 2),
            'methods' => $methods_totals,
            'totalSales' => $totalSalesByMonth,
            'totalExpenses' => $totalExpensesByMonth
        ]);
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

        // Total sold quantity for the day
        $sold = DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->whereDate('sales.date', $date)
            ->sum('sale_details.quantity');

        // Total dispatched quantity for the day
        // Logic: Sales that have a corresponding entry in cashbox_movements (markDispatch creates one)
        $dispatched = DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('cashbox_movements', 'cashbox_movements.sale_id', '=', 'sales.id')
            ->whereDate('sales.date', $date)
            ->sum('sale_details.quantity');

        return response()->json([
            'sold' => (int)$sold,
            'dispatched' => (int)$dispatched
        ]);
    }
}
