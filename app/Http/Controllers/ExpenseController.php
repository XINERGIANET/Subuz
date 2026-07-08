<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\BankLoan;
use App\Exports\ExpensesExport;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use App\Models\LoanPayment;
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
        })->when($request->expense_category_id, function($query, $expense_category_id){
            return $query->where('expense_category_id', $expense_category_id);
        })->when($request->expense_subcategory_id, function($query, $expense_subcategory_id){
            return $query->where('expense_subcategory_id', $expense_subcategory_id);
        })->when(auth()->check() && auth()->user()->hasRole('despachador'), function($query){
            return $query->where('user_id', auth()->id());
        });

        $total_expenses = $query->sum('amount');
        $expenses = $query->latest('date')->paginate(10);

        $financeCategory = $this->syncFinanceExpenseCategory();
        $financeLoans = $this->getFinanceLoanOptions($financeCategory);

        $payment_methods = PaymentMethod::all();
        $categories = ExpenseCategory::with('subcategories')->get();

        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $categories = $categories->filter(function($category) {
                return strtolower(trim($category->name)) === 'operativos';
            })->values();
        }

        $financeCategoryId = $financeCategory->id;
        
        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $descriptions = collect();
        } else {
            $descriptions = Expense::select('description')->distinct()->pluck('description');
        }
        
        return view('expenses.index', compact('expenses', 'payment_methods', 'total_expenses', 'descriptions', 'categories', 'financeCategoryId', 'financeLoans'));
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

        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $category = ExpenseCategory::find($request->expense_category_id);
            if (!$category || strtolower(trim($category->name)) !== 'operativos') {
                return response()->json([
                    'status' => false,
                    'error' => 'Solo tienes permiso para registrar gastos en la categoría operativos.'
                ]);
            }
        }

        try {
            DB::transaction(function() use ($request, $date){
                $loan = null;
                $installmentNumber = null;

                if ($request->bank_loan_id && $request->installment_number) {
                    $loan = BankLoan::with('payments')->findOrFail($request->bank_loan_id);
                    $installmentNumber = (int) $request->installment_number;

                    $alreadyPaid = $loan->payments
                        ->where('installment_number', $installmentNumber)
                        ->count() > 0;

                    if ($alreadyPaid) {
                        throw new \RuntimeException('Esta cuota ya fue registrada en Finanzas.');
                    }
                }

                foreach($request->payments as $payment){
                    if ($loan) {
                        LoanPayment::create([
                            'bank_loan_id' => $loan->id,
                            'amount' => $payment['amount'],
                            'payment_date' => now()->format('Y-m-d'),
                            'installment_number' => $installmentNumber,
                            'payment_method_id' => $payment['method_id'],
                            'notes' => 'Registrado desde Gastos'
                        ]);
                    }

                    Expense::create([
                        'description' => $request->description,
                        'amount' => $payment['amount'],
                        'payment_method_id' => $payment['method_id'],
                        'date' => $date,
                        'real_date' => $request->real_date,
                        'receipt_number' => $request->receipt_number,
                        'operation_number' => $request->operation_number,
                        'expense_category_id' => $request->expense_category_id,
                        'expense_subcategory_id' => $request->expense_subcategory_id,
                        'user_id' => auth()->id()
                    ]);
                }

                if ($loan && $loan->fresh()->remaining_balance <= 0.1) {
                    $loan->update(['status' => 'Pagado']);
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }

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
            'real_date' => $expense->real_date ? Carbon::parse($expense->real_date)->format('Y-m-d') : null,
            'receipt_number' => $expense->receipt_number,
            'operation_number' => $expense->operation_number,
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
            $originalUserId = $expense->user_id;

            // Group update: delete all existing parts of this expense
            Expense::where('description', $expense->description)
                ->where('date', $expense->date)
                ->delete();

            foreach($request->payments as $payment){
                Expense::create([
                    'description' => $request->description,
                    'amount' => $payment['amount'],
                    'payment_method_id' => $payment['method_id'],
                    'date' => $expense->date, // Keep original auto date
                    'real_date' => $request->real_date,
                    'receipt_number' => $request->receipt_number,
                    'operation_number' => $request->operation_number,
                    'expense_category_id' => $request->expense_category_id,
                    'expense_subcategory_id' => $request->expense_subcategory_id,
                    'user_id' => $originalUserId
                ]);
            }
        });

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Expense $expense){
        $expenses = Expense::where('description', $expense->description)
            ->where('date', $expense->date)
            ->get();

        foreach ($expenses as $item) {
            $this->deleteRelatedLoanPayments($item);
        }

        Expense::whereIn('id', $expenses->pluck('id'))->delete();

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
        $fpdf->AddPage('L');
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }
        
        $fpdf->SetXY(210, 10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(70, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $fpdf->SetY(25); // Ajustar Y debajo del logo y la fecha

        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(277, 10, utf8_decode('REPORTE DE GASTOS'), 0, 1, 'C');
        
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
        $fpdf->Cell(277, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(5);

        // --- RESUMEN ---
        $total = $expenses->sum('amount');
        
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(277, 8, utf8_decode('RESUMEN DE GASTOS'), 1, 1, 'C', true);

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
        $fpdf->Cell(277, 8, utf8_decode('DETALLE DE GASTOS'), 1, 1, 'C', true);

        $fpdf->SetFillColor(240, 240, 240);
        $fpdf->SetTextColor(0, 0, 0);

        foreach($byCategory as $catId => $catItems) {
            $catName = $catId ? optional($catItems->first()->category)->name : 'Sin categoría';
            
            $fpdf->SetFont('Montserrat', 'B', 9);
            $fpdf->SetFillColor(220, 220, 220);
            $fpdf->Cell(277, 7, utf8_decode($catName), 1, 1, 'L', true);

            $bySubcat = $catItems->groupBy('expense_subcategory_id');

            foreach($bySubcat as $subId => $subItems) {
                $subName = $subId ? optional($subItems->first()->subcategory)->name : 'Sin subcategoría';
                
                $fpdf->SetFont('Montserrat', 'B', 8);
                $fpdf->SetFillColor(240, 240, 240);
                $fpdf->Cell(277, 6, utf8_decode('    ' . $subName), 1, 1, 'L', true);

                $fpdf->SetFont('Montserrat', 'B', 8);
                $fpdf->Cell(80, 6, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C');
                $fpdf->Cell(25, 6, utf8_decode('MONTO'), 1, 0, 'C');
                $fpdf->Cell(35, 6, utf8_decode('MÉTODO'), 1, 0, 'C');
                $fpdf->Cell(25, 6, utf8_decode('F. REGT.'), 1, 0, 'C');
                $fpdf->Cell(25, 6, utf8_decode('F. REAL'), 1, 0, 'C');
                $fpdf->Cell(45, 6, utf8_decode('COMPROBANTE'), 1, 0, 'C');
                $fpdf->Cell(45, 6, utf8_decode('N° OPERACIÓN'), 1, 1, 'C');

                $fpdf->SetFont('Montserrat', '', 8);
                foreach($subItems as $expense) {
                    $fpdf->Cell(80, 6, utf8_decode(substr($expense->description, 0, 45)), 1);
                    $fpdf->Cell(25, 6, 'S/'.number_format($expense->amount, 2), 1, 0, 'R');
                    $fpdf->Cell(35, 6, utf8_decode(optional($expense->payment_method)->name ?? 'N/A'), 1, 0, 'C');
                    $fpdf->Cell(25, 6, $expense->date->format('d/m/Y'), 1, 0, 'C');
                    $fpdf->Cell(25, 6, $expense->real_date ? date('d/m/Y', strtotime($expense->real_date)) : '-', 1, 0, 'C');
                    $fpdf->Cell(45, 6, utf8_decode(substr($expense->receipt_number ?: '-', 0, 25)), 1, 0, 'C');
                    $fpdf->Cell(45, 6, utf8_decode(substr($expense->operation_number ?: '-', 0, 25)), 1, 1, 'C');
                }
            }
        }

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(277, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReporteGastos_".now()->format('dm').".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('D', $name);
    }

    private function syncFinanceExpenseCategory()
    {
        $financeCategory = ExpenseCategory::firstOrCreate(['name' => 'Finanzas']);

        BankLoan::with('payments')
            ->where('status', 'Activo')
            ->orderBy('bank_name')
            ->get()
            ->filter(function($loan){
                return $loan->remaining_balance > 0.1 && $this->nextPendingInstallment($loan);
            })
            ->each(function($loan) use ($financeCategory){
                ExpenseSubcategory::firstOrCreate([
                    'expense_category_id' => $financeCategory->id,
                    'name' => $this->financeSubcategoryName($loan),
                ]);
            });

        return $financeCategory;
    }

    private function getFinanceLoanOptions(ExpenseCategory $financeCategory)
    {
        return BankLoan::with('payments')
            ->where('status', 'Activo')
            ->orderBy('bank_name')
            ->get()
            ->filter(function($loan){
                return $loan->remaining_balance > 0.1 && $this->nextPendingInstallment($loan);
            })
            ->map(function($loan) use ($financeCategory){
                $installmentNumber = $this->nextPendingInstallment($loan);
                $amount = $loan->monthly_amount ?: ($loan->total_amount / $loan->installments_total);
                $dueDate = Carbon::parse($loan->start_date)->addMonths($installmentNumber - 1);
                $subcategory = ExpenseSubcategory::firstOrCreate([
                    'expense_category_id' => $financeCategory->id,
                    'name' => $this->financeSubcategoryName($loan),
                ]);
                $symbol = $loan->currency === 'USD' ? '$' : 'S/';

                return [
                    'loan_id' => $loan->id,
                    'subcategory_id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'label' => $subcategory->name . ' - Cuota ' . $installmentNumber,
                    'description' => 'Pago de Cuota ' . $installmentNumber . ' - Crédito Banco ' . $loan->bank_name,
                    'installment_number' => $installmentNumber,
                    'amount' => round((float) $amount, 2),
                    'formatted_amount' => $symbol . number_format((float) $amount, 2),
                    'currency' => $loan->currency,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'due_date_label' => $dueDate->format('d/m/Y'),
                ];
            })
            ->values();
    }

    private function nextPendingInstallment(BankLoan $loan)
    {
        $paidInstallments = $loan->payments->pluck('installment_number')->unique()->toArray();

        for ($i = 1; $i <= $loan->installments_total; $i++) {
            if (!in_array($i, $paidInstallments)) {
                return $i;
            }
        }

        return null;
    }

    private function financeSubcategoryName(BankLoan $loan)
    {
        return trim($loan->bank_name);
    }

    private function deleteRelatedLoanPayments(Expense $expense)
    {
        if (!preg_match('/Pago de Cuota\s+(\d+)\s+-\s+Cr[eÃ©]dito Banco\s+(.+)/i', $expense->description, $matches)) {
            return;
        }

        $installmentNumber = (int) $matches[1];
        $bankName = trim($matches[2]);
        $loans = BankLoan::withTrashed()->where('bank_name', $bankName)->get();

        foreach ($loans as $loan) {
            LoanPayment::where('bank_loan_id', $loan->id)
                ->where('installment_number', $installmentNumber)
                ->whereDate('payment_date', $expense->date->toDateString())
                ->where('payment_method_id', $expense->payment_method_id)
                ->where('amount', $expense->amount)
                ->delete();

            if (!$loan->trashed() && $loan->fresh()->remaining_balance > 0.1) {
                $loan->update(['status' => 'Activo']);
            }
        }
    }
}
