<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;
use App\Models\Week;
use App\Models\Sale;
use App\Models\Payment;

class PaymentController extends Controller
{

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'sale_id' => 'required',
            'payment_method_id' => 'required',
            'type' => 'required'
        ]);

        $validator->sometimes('amount', 'required', function($input){
            return $input->type == 'Credito';
        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        if($request->type == 'Credito'){



            $sale = Sale::findOrFail($request->sale_id);

            

            if($request->amount > $sale->debt){

                return response()->json([
                    'status' => false,
                    'error' => 'El monto a pagar debe ser menor o igual a la deuda.'
                ]);

            }

            $debt = $sale->debt - $request->amount;
            $paid = $debt == 0 ? 1 : 0;

            $sale->update([
                'debt' => $debt,
                'paid' => $paid
            ]);

            Payment::create([
                'sale_id' => $request->sale_id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $request->amount,
                'date' => now()
            ]);

        }elseif($request->type == 'Pago pendiente'){
            $sale = Sale::find($request->sale_id);
            
            $sale->update([
                'debt' => 0,
                'paid' => 1
            ]);

            Payment::create([
                'sale_id' => $request->sale_id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $sale->total,
                'date' => now()
            ]);

            $request->merge(['amount' => $sale->total]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function excel(Request $request){
        $name = "ReportePagos_".now()->format('dm').".xlsx";
        return Excel::download(new PaymentsExport, $name);
    }
}
