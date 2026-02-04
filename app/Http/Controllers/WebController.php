<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Expense;

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
            'totalSales' => $totalSalesByMonth,
            'totalExpenses' => $totalExpensesByMonth
        ]);
    }

    public function dashboardProduct(Request $request){
        $salesYear = SaleDetail::where('product_id', $request->product_id)
            ->when($request->year, function($query, $year){
                return $query->whereHas('sale', function($query) use ($year){
                    $query->whereYear('date', $year);
                });
            })->sum(DB::raw('price * quantity'));

        $salesMonth = SaleDetail::where('product_id', $request->product_id)
            ->when($request->year, function($query, $year){
                return $query->whereHas('sale', function($query) use ($year){
                    $query->whereYear('date', $year);
                });
            })->when($request->month, function($query, $month){
                return $query->whereHas('sale', function($query) use ($month){
                    $query->whereMonth('date', $month);
                });
            })->sum(DB::raw('price * quantity'));



        
        $salesByMonth = DB::table('sale_details')->select(
            DB::raw('MONTH(sales.date) as month'),
            DB::raw('SUM(sale_details.price * sale_details.quantity) as total'),
        )->join('sales', 'sales.id', 'sale_details.sale_id')
            ->where('product_id', $request->product_id)
            ->when($request->year, function($query, $date){
                return $query->whereYear('sales.date', $date);
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
}
