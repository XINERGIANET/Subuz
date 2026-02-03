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
        })->paginate(10);
        $payment_methods = PaymentMethod::all();
        $total_expenses = $expenses->sum('amount');
        return view('expenses.index', compact('expenses', 'payment_methods', 'total_expenses'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'amount' => 'required|numeric',
            'payment_method_id' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $request->merge(['date' => now()->format('Y-m-d H:i:s')]);

        Expense::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Expense $expense){
        return response()->json($expense);
    }

    public function update(Request $request, Expense $expense){
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'amount' => 'required|numeric',
            'payment_method_id' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $expense->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Expense $expense){
        $expense->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function excel(Request $request){
        $name = "ReporteGastos_".now()->format('dm').".xlsx";
        return Excel::download(new ExpensesExport, $name);
    }
}
