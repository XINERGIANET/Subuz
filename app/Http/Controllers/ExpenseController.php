<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use App\Models\Expense;
use App\Models\PaymentMethod;

class ExpenseController extends Controller
{
    public function index(Request $request){
        $expenses = Expense::when($request->month, function($query, $month){
            return $query->whereMonth('date', $month);
        })->when($request->year, function($query, $year){
            return $query->whereYear('date', $year);
        })->latest('date')->paginate(10);
        $payment_methods = PaymentMethod::all();
        $total_expenses = $expenses->sum('amount');
        return view('expenses.index', compact('expenses', 'payment_methods', 'total_expenses'));
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
        $name = "ReporteGastos_".now()->format('dm').".xlsx";
        return Excel::download(new ExpensesExport, $name);
    }
}
