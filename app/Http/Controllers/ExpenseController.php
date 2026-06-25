<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\ExpenseCategory;
use Codedge\Fpdf\Fpdf\Fpdf;

class ExpenseController extends Controller
{
    public function index(Request $request){
        $query = Expense::when($request->month, function($query, $month){
            return $query->whereMonth('date', $month);
        })->when($request->year, function($query, $year){
            return $query->whereYear('date', $year);
        })->when($request->from_date, function($query, $from){
            return $query->whereDate('date', '>=', $from);
        })->when($request->to_date, function($query, $to){
            return $query->whereDate('date', '<=', $to);
        })->when($request->payment_method_id, function($query, $payment_method_id){
            return $query->where('payment_method_id', $payment_method_id);
        });

        $total_expenses = $query->sum('amount');
        $expenses = $query->latest('date')->paginate(10);

        $payment_methods = PaymentMethod::all();
        $categories = ExpenseCategory::with('subcategories')->get();
        $descriptions = Expense::select('description')->distinct()->pluck('description');
        return view('expenses.index', compact('expenses', 'payment_methods', 'total_expenses', 'descriptions', 'categories'));
    }

    public function indicators(Request $request){
        $startDate = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('end_date') ?: now()->endOfMonth()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $categoryId = $request->input('expense_category_id');

        $buildQuery = function ($from, $to) use ($categoryId) {
            return Expense::with(['category', 'subcategory', 'payment_method'])
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->when($categoryId, function($query, $categoryId){
                    return $query->where('expense_category_id', $categoryId);
                });
        };

        $expenses = $buildQuery($startDate, $endDate)->get();
        $expenseGroups = $expenses->groupBy(function($expense){
            return $expense->description . '|' . $expense->date->format('Y-m-d H:i:s');
        });

        $periodDays = max(1, $start->diffInDays($end) + 1);
        $previousStartDate = $start->copy()->subDays($periodDays)->format('Y-m-d');
        $previousEndDate = $start->copy()->subDay()->format('Y-m-d');
        $previousExpenses = $buildQuery($previousStartDate, $previousEndDate)->get();
        $previousGroups = $previousExpenses->groupBy(function($expense){
            return $expense->description . '|' . $expense->date->format('Y-m-d H:i:s');
        });

        $totalAmount = (float) $expenses->sum('amount');
        $expenseCount = $expenseGroups->count();
        $averageAmount = $expenseCount > 0 ? $totalAmount / $expenseCount : 0;

        $previousTotal = (float) $previousExpenses->sum('amount');
        $previousCount = $previousGroups->count();
        $previousAverage = $previousCount > 0 ? $previousTotal / $previousCount : 0;

        $percentChange = function ($current, $previous) {
            if ((float) $previous === 0.0) {
                return $current > 0 ? 100 : 0;
            }

            return round((($current - $previous) / $previous) * 100, 1);
        };

        $metricChanges = [
            'total' => $percentChange($totalAmount, $previousTotal),
            'count' => $percentChange($expenseCount, $previousCount),
            'average' => $percentChange($averageAmount, $previousAverage),
        ];

        $palette = ['#3B82F6', '#22C55E', '#F59E0B', '#8B5CF6', '#06B6D4', '#EF4444', '#14B8A6', '#F97316'];

        $categorySummary = $expenses->groupBy(function($expense){
            return $expense->expense_category_id ?: 'none';
        })->map(function($items) use ($totalAmount){
            $first = $items->first();
            $total = (float) $items->sum('amount');

            return [
                'name' => optional($first->category)->name ?: 'Sin categoria',
                'total' => round($total, 2),
                'percent' => $totalAmount > 0 ? round(($total / $totalAmount) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values()->map(function($item, $index) use ($palette){
            $item['color'] = $palette[$index % count($palette)];
            return $item;
        });

        $topCategory = $categorySummary->first() ?: [
            'name' => 'Sin datos',
            'total' => 0,
            'percent' => 0,
            'color' => $palette[0],
        ];

        $topCategories = $categorySummary->take(5)->values();

        $subcategorySummary = $expenses->groupBy(function($expense){
            return $expense->expense_subcategory_id ?: 'none';
        })->map(function($items) use ($totalAmount){
            $first = $items->first();
            $total = (float) $items->sum('amount');

            return [
                'name' => optional($first->subcategory)->name ?: 'Sin subcategoria',
                'total' => round($total, 2),
                'percent' => $totalAmount > 0 ? round(($total / $totalAmount) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->take(8)->values();

        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $evolutionLabels = [];
        $evolutionData = [];
        $evolutionLabel = 'Gasto Diario (S/)';

        if ($periodDays <= 62) {
            $dailyTotals = $expenses->groupBy(function($expense){
                return $expense->date->format('Y-m-d');
            })->map(function($items){
                return round((float) $items->sum('amount'), 2);
            });

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $key = $date->format('Y-m-d');
                $evolutionLabels[] = $date->format('d/m');
                $evolutionData[] = $dailyTotals->get($key, 0);
            }
        } else {
            $evolutionLabel = 'Gasto Mensual (S/)';
            $monthlyTotals = $expenses->groupBy(function($expense){
                return $expense->date->format('Y-m');
            })->map(function($items){
                return round((float) $items->sum('amount'), 2);
            });

            for ($date = $start->copy()->startOfMonth(); $date->lte($end); $date->addMonth()) {
                $key = $date->format('Y-m');
                $evolutionLabels[] = $monthNames[(int) $date->format('n')] . ' ' . $date->format('Y');
                $evolutionData[] = $monthlyTotals->get($key, 0);
            }
        }

        $categories = ExpenseCategory::orderBy('name')->get();

        return view('expenses.indicators', compact(
            'startDate',
            'endDate',
            'categoryId',
            'categories',
            'totalAmount',
            'expenseCount',
            'averageAmount',
            'metricChanges',
            'categorySummary',
            'topCategory',
            'topCategories',
            'subcategorySummary',
            'evolutionLabels',
            'evolutionData',
            'evolutionLabel'
        ));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'payments' => 'required|array',
            'payments.*.method_id' => 'required|numeric',
            'payments.*.amount' => 'required|numeric|min:0.01'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $date = now()->format('Y-m-d H:i:s');

        DB::transaction(function() use ($request, $date){
            foreach($request->payments as $payment){
                Expense::create([
                    'description' => $request->description,
                    'amount' => $payment['amount'],
                    'payment_method_id' => $payment['method_id'],
                    'date' => $date,
                    'expense_category_id' => $request->expense_category_id,
                    'expense_subcategory_id' => $request->expense_subcategory_id
                ]);
            }
        });

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Expense $expense){
        $payments = Expense::where('description', $expense->description)
            ->where('date', $expense->date)
            ->get();

        return response()->json([
            'id' => $expense->id,
            'description' => $expense->description,
            'expense_category_id' => $expense->expense_category_id,
            'expense_subcategory_id' => $expense->expense_subcategory_id,
            'payments' => $payments->map(function($p){
                return [
                    'method_id' => $p->payment_method_id,
                    'amount' => $p->amount
                ];
            })
        ]);
    }

    public function update(Request $request, Expense $expense){
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'payments' => 'required|array',
            'payments.*.method_id' => 'required|numeric',
            'payments.*.amount' => 'required|numeric|min:0.01'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        DB::transaction(function() use ($request, $expense){
            // Group update: delete all existing parts of this expense
            Expense::where('description', $expense->description)
                ->where('date', $expense->date)
                ->delete();

            foreach($request->payments as $payment){
                Expense::create([
                    'description' => $request->description,
                    'amount' => $payment['amount'],
                    'payment_method_id' => $payment['method_id'],
                    'date' => $expense->date, // Keep original date
                    'expense_category_id' => $request->expense_category_id,
                    'expense_subcategory_id' => $request->expense_subcategory_id
                ]);
            }
        });

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Expense $expense){
        Expense::where('description', $expense->description)
            ->where('date', $expense->date)
            ->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function excel(Request $request){
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = "ReporteGastos_".now()->format('dm').".xlsx";
        return Excel::download(new ExpensesExport($request), $name);
    }

    public function pdf(Request $request){
        $expenses = Expense::with('payment_method')
            ->when($request->month, function($query, $month){
                return $query->whereMonth('date', $month);
            })->when($request->year, function($query, $year){
                return $query->whereYear('date', $year);
            })->when($request->from_date, function($query, $from){
                return $query->whereDate('date', '>=', $from);
            })->when($request->to_date, function($query, $to){
                return $query->whereDate('date', '<=', $to);
            })->when($request->payment_method_id, function($query, $payment_method_id){
                return $query->where('payment_method_id', $payment_method_id);
            })->latest('date')->get();

        $fpdf = new Fpdf;
        $fpdf->AddPage();
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }
        
        // Fecha de generación arriba a la derecha
        $fpdf->SetXY(130, 10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(70, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $fpdf->SetY(25); // Ajustar Y debajo del logo y la fecha

        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('REPORTE DE GASTOS'), 0, 1, 'C');
        
        $months = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'];
        $period = "Periodo: ";
        if($request->from_date || $request->to_date) {
            if($request->from_date) $period .= "Desde " . date('d/m/Y', strtotime($request->from_date)) . " ";
            if($request->to_date) $period .= "Hasta " . date('d/m/Y', strtotime($request->to_date));
        } else {
            if($request->month) $period .= $months[$request->month] . " ";
            if($request->year) $period .= $request->year;
            if(!$request->month && !$request->year) $period .= "Todos los registros";
        }
        
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(190, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(5);

        // --- RESUMEN ---
        $total = $expenses->sum('amount');
        
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(190, 8, utf8_decode('RESUMEN DE GASTOS'), 1, 1, 'C', true);

        // Totales por Categoría
        $byCategory = $expenses->groupBy('expense_category_id');
        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->Cell(130, 6, utf8_decode('Por Categoría'), 'LR', 0, 'L');
        $fpdf->Cell(60, 6, utf8_decode('Monto'), 'LR', 1, 'R');
        $fpdf->SetFont('Montserrat', '', 9);
        
        foreach($byCategory as $catId => $items) {
            $catName = $catId ? optional($items->first()->category)->name : 'Sin categoría';
            $catTotal = $items->sum('amount');
            $fpdf->Cell(130, 6, utf8_decode($catName), 'LR', 0, 'L');
            $fpdf->Cell(60, 6, 'S/'.number_format($catTotal, 2), 'LR', 1, 'R');
        }
        $fpdf->Cell(190, 0, '', 'T', 1);

        // Totales por Método de Pago
        $byMethod = $expenses->groupBy('payment_method_id');
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->Cell(130, 6, utf8_decode('Por Método de Pago'), 'LR', 0, 'L');
        $fpdf->Cell(60, 6, utf8_decode('Monto'), 'LR', 1, 'R');
        $fpdf->SetFont('Montserrat', '', 9);

        foreach($byMethod as $methodId => $items) {
            $methodName = $methodId ? optional($items->first()->payment_method)->name : 'N/A';
            $methodTotal = $items->sum('amount');
            $fpdf->Cell(130, 6, utf8_decode($methodName), 'LR', 0, 'L');
            $fpdf->Cell(60, 6, 'S/'.number_format($methodTotal, 2), 'LR', 1, 'R');
        }
        
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(130, 8, 'TOTAL GENERAL', 1, 0, 'R');
        $fpdf->Cell(60, 8, 'S/'.number_format($total, 2), 1, 1, 'R');

        $fpdf->Ln(10);

        // --- DETALLE ---
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(190, 8, utf8_decode('DETALLE DE GASTOS'), 1, 1, 'C', true);

        $fpdf->SetFillColor(240, 240, 240);
        $fpdf->SetTextColor(0, 0, 0);

        foreach($byCategory as $catId => $catItems) {
            $catName = $catId ? optional($catItems->first()->category)->name : 'Sin categoría';
            
            $fpdf->SetFont('Montserrat', 'B', 9);
            $fpdf->SetFillColor(220, 220, 220);
            $fpdf->Cell(190, 7, utf8_decode($catName), 1, 1, 'L', true);

            $bySubcat = $catItems->groupBy('expense_subcategory_id');

            foreach($bySubcat as $subId => $subItems) {
                $subName = $subId ? optional($subItems->first()->subcategory)->name : 'Sin subcategoría';
                
                $fpdf->SetFont('Montserrat', 'B', 8);
                $fpdf->SetFillColor(240, 240, 240);
                $fpdf->Cell(190, 6, utf8_decode('    ' . $subName), 1, 1, 'L', true);

                $fpdf->SetFont('Montserrat', 'B', 8);
                $fpdf->Cell(90, 6, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C');
                $fpdf->Cell(30, 6, utf8_decode('MONTO'), 1, 0, 'C');
                $fpdf->Cell(40, 6, utf8_decode('MÉTODO'), 1, 0, 'C');
                $fpdf->Cell(30, 6, utf8_decode('FECHA'), 1, 1, 'C');

                $fpdf->SetFont('Montserrat', '', 8);
                foreach($subItems as $expense) {
                    $fpdf->Cell(90, 6, utf8_decode(substr($expense->description, 0, 45)), 1);
                    $fpdf->Cell(30, 6, 'S/'.number_format($expense->amount, 2), 1, 0, 'R');
                    $fpdf->Cell(40, 6, utf8_decode(optional($expense->payment_method)->name ?? 'N/A'), 1, 0, 'C');
                    $fpdf->Cell(30, 6, $expense->date->format('d/m/Y'), 1, 1, 'C');
                }
            }
        }

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReporteGastos_".now()->format('dm').".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('D', $name);
    }
}
