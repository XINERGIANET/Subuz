<?php

namespace App\Http\Controllers;

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
