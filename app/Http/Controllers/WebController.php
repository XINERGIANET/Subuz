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
            $transfers = CashboxMovement::where('type', 'transfer')->where('payment_method_id', $method->id)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->sum('amount');
            $expense = Expense::where('payment_method_id', $method->id)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->sum('amount');
            $balance = ($payments + $manual + $transfers) - $expense;

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

    public function dashboardDetail(Request $request)
    {
        $type = $request->type; // 'efectivo', 'transferencias', 'yape_plin', 'pendiente', 'ventas', 'gastos', 'ingresos_caja', 'balance'
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $items = [];
        $title = 'Detalle de Movimientos';
        $summary = [
            'total_income' => 0,
            'total_expense' => 0,
            'net_total' => 0
        ];

        // Helper closures for payment methods classification
        $allMethods = PaymentMethod::all();

        $classifyMethod = function($name) {
            $n = strtolower($name);
            if (str_contains($n, 'yape') || str_contains($n, 'plin')) {
                return 'yape_plin';
            }
            if (str_contains($n, 'efectivo') || $n === 'caja') {
                return 'efectivo';
            }
            return 'transferencias';
        };

        $getMethodIds = function($category) use ($allMethods, $classifyMethod) {
            $ids = [];
            foreach ($allMethods as $m) {
                if ($classifyMethod($m->name) === $category) {
                    $ids[] = $m->id;
                }
            }
            return $ids;
        };

        if (in_array($type, ['efectivo', 'transferencias', 'yape_plin'])) {
            $methodIds = $getMethodIds($type);
            $titleNames = [
                'efectivo' => 'Efectivo',
                'transferencias' => 'Transferencias Bancarias',
                'yape_plin' => 'Yape / Plin'
            ];
            $title = 'Detalle de ' . ($titleNames[$type] ?? 'Método de Pago');

            // 1. Cobros / Pagos de ventas (Payment model)
            $payments = Payment::with(['sale.client', 'sale.dispatcher', 'payment_method'])
                ->whereIn('payment_method_id', $methodIds)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($payments as $p) {
                $time = $p->date ? Carbon::parse($p->date)->format('H:i:s') : '-';
                $dateTimeStr = $p->date ? Carbon::parse($p->date)->format('d/m/Y H:i') : '-';
                $clientName = optional(optional($p->sale)->client)->name ?? 'Consumidor Final';
                $guide = optional($p->sale)->guide ? 'Guía: ' . $p->sale->guide : 'Venta #' . ($p->sale_id ?? '-');
                $dispatcher = optional(optional($p->sale)->dispatcher)->name ?? '-';

                // Try to find corresponding CashboxMovement to get cashbox info
                $boxMov = CashboxMovement::with('cashbox.openedBy')
                    ->where('sale_id', $p->sale_id)
                    ->where('payment_method_id', $p->payment_method_id)
                    ->first();

                $cashboxName = 'Caja General';
                if ($boxMov && $boxMov->cashbox) {
                    $cashboxName = 'Caja #' . $boxMov->cashbox->id . ' (' . (optional($boxMov->cashbox->openedBy)->name ?? 'Caja') . ')';
                }

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'income',
                    'type_label' => 'Cobro / Venta',
                    'method' => optional($p->payment_method)->name ?? '-',
                    'concept' => $guide . ' - ' . $clientName,
                    'cashbox' => $cashboxName,
                    'user' => $dispatcher !== '-' ? $dispatcher : (optional(optional($p->sale)->user)->name ?? 'Sistema'),
                    'amount' => (float)$p->amount
                ];
                $summary['total_income'] += (float)$p->amount;
            }

            // 2. Ingresos manuales y transferencias de caja (CashboxMovement)
            $movements = CashboxMovement::with(['cashbox.openedBy', 'user', 'dispatcher', 'payment_method', 'sale.client'])
                ->whereIn('payment_method_id', $methodIds)
                ->whereIn('type', ['income', 'transfer'])
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($movements as $m) {
                $time = $m->date ? Carbon::parse($m->date)->format('H:i:s') : '-';
                $dateTimeStr = $m->date ? Carbon::parse($m->date)->format('d/m/Y H:i') : '-';
                $cashboxName = $m->cashbox ? 'Caja #' . $m->cashbox->id . ' (' . (optional($m->cashbox->openedBy)->name ?? 'Caja') . ')' : 'Caja General';
                
                $isTransfer = $m->type === 'transfer';
                $amountVal = (float)$m->amount;
                $movType = $amountVal >= 0 ? 'income' : 'expense';
                $typeLabel = $isTransfer ? ($amountVal >= 0 ? 'Transferencia (Entrada)' : 'Transferencia (Salida)') : 'Ingreso Manual';

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => $movType,
                    'type_label' => $typeLabel,
                    'method' => optional($m->payment_method)->name ?? '-',
                    'concept' => $m->note ?: ($isTransfer ? 'Transferencia entre cuentas' : 'Ingreso de Caja'),
                    'cashbox' => $cashboxName,
                    'user' => optional($m->user)->name ?? 'Sistema',
                    'amount' => abs($amountVal)
                ];

                if ($movType === 'income') {
                    $summary['total_income'] += abs($amountVal);
                } else {
                    $summary['total_expense'] += abs($amountVal);
                }
            }

            // 3. Egresos / Gastos (Expense)
            $expenses = Expense::with(['payment_method', 'category', 'subcategory', 'user'])
                ->whereIn('payment_method_id', $methodIds)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($expenses as $e) {
                $time = $e->date ? Carbon::parse($e->date)->format('H:i:s') : '-';
                $dateTimeStr = $e->date ? Carbon::parse($e->date)->format('d/m/Y H:i') : '-';
                $catName = optional($e->category)->name ?? 'Gasto';
                $concept = $e->description ? $catName . ': ' . $e->description : $catName;

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'expense',
                    'type_label' => 'Gasto / Egreso',
                    'method' => optional($e->payment_method)->name ?? '-',
                    'concept' => $concept,
                    'cashbox' => 'Caja / Banco',
                    'user' => optional($e->user)->name ?? 'Sistema',
                    'amount' => (float)$e->amount
                ];
                $summary['total_expense'] += (float)$e->amount;
            }

        } elseif ($type === 'pendiente') {
            $title = 'Detalle de Ventas Pendientes / Créditos';
            
            $pendingSales = Sale::with(['client', 'dispatcher', 'payment_method'])
                ->where('status', '!=', 'Anulado')
                ->where('paid', 0)
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($pendingSales as $s) {
                $time = $s->date ? Carbon::parse($s->date)->format('H:i:s') : '-';
                $dateTimeStr = $s->date ? Carbon::parse($s->date)->format('d/m/Y H:i') : '-';
                $clientName = optional($s->client)->name ?? 'Consumidor Final';
                $guide = $s->guide ? 'Guía: ' . $s->guide : 'Pedido #' . $s->id;
                $dispatcher = optional($s->dispatcher)->name ?? '-';

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'pending',
                    'type_label' => $s->type ?: 'Pendiente',
                    'method' => optional($s->payment_method)->name ?? 'Por Definir',
                    'concept' => $guide . ' - ' . $clientName,
                    'cashbox' => 'Cuentas x Cobrar',
                    'user' => $dispatcher,
                    'amount' => (float)$s->total
                ];
                $summary['total_income'] += (float)$s->total;
            }

        } elseif ($type === 'gastos') {
            $title = 'Detalle de Gastos y Egresos';

            $expenses = Expense::with(['payment_method', 'category', 'subcategory', 'user'])
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($expenses as $e) {
                $time = $e->date ? Carbon::parse($e->date)->format('H:i:s') : '-';
                $dateTimeStr = $e->date ? Carbon::parse($e->date)->format('d/m/Y H:i') : '-';
                $catName = optional($e->category)->name ?? 'Gasto';
                $concept = $e->description ? $catName . ': ' . $e->description : $catName;

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'expense',
                    'type_label' => 'Egreso / ' . $catName,
                    'method' => optional($e->payment_method)->name ?? 'Sin medio',
                    'concept' => $concept,
                    'cashbox' => 'Caja / Banco',
                    'user' => optional($e->user)->name ?? 'Sistema',
                    'amount' => (float)$e->amount
                ];
                $summary['total_expense'] += (float)$e->amount;
            }

        } elseif ($type === 'ingresos_caja') {
            $title = 'Detalle de Ingresos Manuales a Caja';

            $incomes = CashboxMovement::with(['cashbox.openedBy', 'user', 'payment_method'])
                ->where('type', 'income')
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($incomes as $m) {
                $time = $m->date ? Carbon::parse($m->date)->format('H:i:s') : '-';
                $dateTimeStr = $m->date ? Carbon::parse($m->date)->format('d/m/Y H:i') : '-';
                $cashboxName = $m->cashbox ? 'Caja #' . $m->cashbox->id . ' (' . (optional($m->cashbox->openedBy)->name ?? 'Caja') . ')' : 'Caja General';

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'income',
                    'type_label' => 'Ingreso Manual',
                    'method' => optional($m->payment_method)->name ?? 'Efectivo',
                    'concept' => $m->note ?: 'Ingreso a Caja',
                    'cashbox' => $cashboxName,
                    'user' => optional($m->user)->name ?? 'Sistema',
                    'amount' => (float)$m->amount
                ];
                $summary['total_income'] += (float)$m->amount;
            }

        } elseif ($type === 'ventas') {
            $title = 'Detalle de Ventas Realizadas';

            $sales = Sale::with(['client', 'dispatcher', 'payment_method'])
                ->where('status', '!=', 'Anulado')
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->orderBy('date', 'desc')
                ->get();

            foreach ($sales as $s) {
                $time = $s->date ? Carbon::parse($s->date)->format('H:i:s') : '-';
                $dateTimeStr = $s->date ? Carbon::parse($s->date)->format('d/m/Y H:i') : '-';
                $clientName = optional($s->client)->name ?? 'Consumidor Final';
                $guide = $s->guide ? 'Guía: ' . $s->guide : 'Pedido #' . $s->id;
                $statusPaid = $s->paid ? ' (Pagado)' : ' (Pendiente)';

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'sale',
                    'type_label' => $s->type . $statusPaid,
                    'method' => optional($s->payment_method)->name ?? ($s->paid ? 'Varios' : 'Pendiente'),
                    'concept' => $guide . ' - ' . $clientName,
                    'cashbox' => 'Comercial',
                    'user' => optional($s->dispatcher)->name ?? '-',
                    'amount' => (float)$s->total
                ];
                $summary['total_income'] += (float)$s->total;
            }

        } elseif ($type === 'balance') {
            $title = 'Detalle del Balance Total (Ingresos vs Egresos)';

            // Combine all Payments, Manual Incomes, and Expenses
            $payments = Payment::with(['sale.client', 'sale.dispatcher', 'payment_method'])
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->get();

            foreach ($payments as $p) {
                $time = $p->date ? Carbon::parse($p->date)->format('H:i:s') : '-';
                $dateTimeStr = $p->date ? Carbon::parse($p->date)->format('d/m/Y H:i') : '-';
                $clientName = optional(optional($p->sale)->client)->name ?? 'Consumidor Final';
                $guide = optional($p->sale)->guide ? 'Guía: ' . $p->sale->guide : 'Venta #' . ($p->sale_id ?? '-');

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'income',
                    'type_label' => 'Cobro / Venta',
                    'method' => optional($p->payment_method)->name ?? '-',
                    'concept' => $guide . ' - ' . $clientName,
                    'cashbox' => 'Caja / Cobros',
                    'user' => optional(optional($p->sale)->dispatcher)->name ?? 'Sistema',
                    'amount' => (float)$p->amount
                ];
                $summary['total_income'] += (float)$p->amount;
            }

            $manualIncomes = CashboxMovement::with(['cashbox.openedBy', 'user', 'payment_method'])
                ->where('type', 'income')
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->get();

            foreach ($manualIncomes as $m) {
                $time = $m->date ? Carbon::parse($m->date)->format('H:i:s') : '-';
                $dateTimeStr = $m->date ? Carbon::parse($m->date)->format('d/m/Y H:i') : '-';
                $cashboxName = $m->cashbox ? 'Caja #' . $m->cashbox->id . ' (' . (optional($m->cashbox->openedBy)->name ?? 'Caja') . ')' : 'Caja General';

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'income',
                    'type_label' => 'Ingreso Manual',
                    'method' => optional($m->payment_method)->name ?? 'Efectivo',
                    'concept' => $m->note ?: 'Ingreso a Caja',
                    'cashbox' => $cashboxName,
                    'user' => optional($m->user)->name ?? 'Sistema',
                    'amount' => (float)$m->amount
                ];
                $summary['total_income'] += (float)$m->amount;
            }

            $expenses = Expense::with(['payment_method', 'category', 'user'])
                ->when($start_date, function($q, $sd){ return $q->whereDate('date', '>=', $sd); })
                ->when($end_date, function($q, $ed){ return $q->whereDate('date', '<=', $ed); })
                ->get();

            foreach ($expenses as $e) {
                $time = $e->date ? Carbon::parse($e->date)->format('H:i:s') : '-';
                $dateTimeStr = $e->date ? Carbon::parse($e->date)->format('d/m/Y H:i') : '-';
                $catName = optional($e->category)->name ?? 'Gasto';

                $items[] = [
                    'date' => $dateTimeStr,
                    'time' => $time,
                    'type' => 'expense',
                    'type_label' => 'Egreso / ' . $catName,
                    'method' => optional($e->payment_method)->name ?? 'Sin medio',
                    'concept' => $e->description ?: $catName,
                    'cashbox' => 'Caja / Banco',
                    'user' => optional($e->user)->name ?? 'Sistema',
                    'amount' => (float)$e->amount
                ];
                $summary['total_expense'] += (float)$e->amount;
            }
        }

        $summary['net_total'] = $summary['total_income'] - $summary['total_expense'];

        return response()->json([
            'status' => true,
            'title' => $title,
            'summary' => [
                'total_income' => number_format($summary['total_income'], 2),
                'total_expense' => number_format($summary['total_expense'], 2),
                'net_total' => number_format($summary['net_total'], 2)
            ],
            'items' => $items
        ]);
    }
}
