<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use App\Models\Expense;
use App\Models\PaymentMethod;
use Codedge\Fpdf\Fpdf\Fpdf;

class ExpenseController extends Controller
{
    public function index(Request $request){
        $expenses = Expense::when($request->month, function($query, $month){
            return $query->whereMonth('date', $month);
        })->when($request->year, function($query, $year){
            return $query->whereYear('date', $year);
        })->when($request->from_date, function($query, $from){
            return $query->whereDate('date', '>=', $from);
        })->when($request->to_date, function($query, $to){
            return $query->whereDate('date', '<=', $to);
        })->latest('date')->paginate(10);
        $payment_methods = PaymentMethod::all();
        $total_expenses = $expenses->sum('amount');
        $descriptions = Expense::select('description')->distinct()->pluck('description');
        return view('expenses.index', compact('expenses', 'payment_methods', 'total_expenses', 'descriptions'));
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
                    'date' => $date
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
                    'date' => $expense->date // Keep original date
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
            })->latest('date')->get();

        $fpdf = new Fpdf;
        $fpdf->AddPage();
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }
        
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
        $fpdf->Ln(10);

        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        
        $fpdf->Cell(80, 10, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('MONTO'), 1, 0, 'C', true);
        $fpdf->Cell(50, 10, utf8_decode('MÉTODO DE PAGO'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('FECHA'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);
        $total = 0;
        
        foreach($expenses as $expense){
            $fpdf->Cell(80, 8, utf8_decode(substr($expense->description, 0, 40)), 1);
            $fpdf->Cell(30, 8, 'S/'.number_format($expense->amount, 2), 1, 0, 'R');
            $fpdf->Cell(50, 8, utf8_decode(optional($expense->payment_method)->name ?? 'N/A'), 1, 0, 'C');
            $fpdf->Cell(30, 8, $expense->date->format('d/m/Y'), 1, 1, 'C');
            $total += $expense->amount;
        }

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(80, 10, 'TOTAL', 1, 0, 'R');
        $fpdf->Cell(30, 10, 'S/'.number_format($total, 2), 1, 0, 'R');
        $fpdf->Cell(80, 10, '', 1, 1);

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReporteGastos_".now()->format('dm').".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('D', $name);
    }
}
