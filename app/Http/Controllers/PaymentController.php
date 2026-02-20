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
use App\Models\Cashbox;
use App\Models\CashboxMovement;

class PaymentController extends Controller
{

    public function store(Request $request){

        // Transform single payment to array structure if necessary (backward compatibility)
        if (!$request->has('payments') && $request->has('payment_method_id')) {
            $request->merge([
                'payments' => [
                    [
                        'payment_method_id' => $request->payment_method_id,
                        'amount' => $request->amount
                    ]
                ]
            ]);
        }

        $validator = Validator::make($request->all(), [
            'sale_id' => 'required',
            'payments' => 'required|array',
            'payments.*.payment_method_id' => 'required',
            'type' => 'required'
        ]);

        $validator->sometimes('payments.*.amount', 'required|numeric|min:0', function($input){
            return $input->type == 'Credito';
        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $cashbox = Cashbox::currentOpen();

        if(!$cashbox){
            return response()->json([
                'status' => false,
                'error' => 'Debe aperturar caja antes de registrar el pago.'
            ]);
        }

        $sale = Sale::findOrFail($request->sale_id);

        try {
            DB::transaction(function() use ($request, $sale, $cashbox){
                
                if($request->type == 'Credito'){

                    $totalAmount = floatval(collect($request->payments)->sum('amount'));

                    // Check if total amount exceeds debt (with small tolerance for float issues)
                    if(round($totalAmount, 2) > round($sale->debt, 2)){
                        throw new \Exception('El monto total a pagar supera la deuda actual.');
                    }

                    $debt = $sale->debt - $totalAmount;
                    $paid = $debt <= 0 ? 1 : 0;

                    $sale->update([
                        'debt' => $debt,
                        'paid' => $paid
                    ]);

                    foreach($request->payments as $paymentData){
                        Payment::create([
                            'sale_id' => $sale->id,
                            'payment_method_id' => $paymentData['payment_method_id'],
                            'amount' => $paymentData['amount'],
                            'date' => now()
                        ]);

                        CashboxMovement::create([
                            'cashbox_id' => $cashbox->id,
                            'sale_id' => $sale->id,
                            'user_id' => auth()->id(),
                            'payment_method_id' => $paymentData['payment_method_id'],
                            'type' => 'paid',
                            'amount' => $paymentData['amount'],
                            'date' => now()
                        ]);
                    }

                }elseif($request->type == 'Pago pendiente' || $request->type == 'Contado'){
                    
                    $totalAmount = floatval(collect($request->payments)->sum('amount'));

                    // For pending/cash, we expect the FULL amount to be paid to clear it.
                    if(abs($totalAmount - floatval($sale->total)) > 0.01){
                        throw new \Exception("La suma de los pagos (S/".number_format($totalAmount, 2).") debe ser igual al total de la venta (S/".number_format($sale->total, 2).").");
                    }

                    $sale->update([
                        'debt' => 0,
                        'paid' => 1
                    ]);

                    foreach($request->payments as $paymentData){
                        Payment::create([
                            'sale_id' => $sale->id,
                            'payment_method_id' => $paymentData['payment_method_id'],
                            'amount' => $paymentData['amount'],
                            'date' => now()
                        ]);

                        CashboxMovement::create([
                            'cashbox_id' => $cashbox->id,
                            'sale_id' => $sale->id,
                            'user_id' => auth()->id(),
                            'payment_method_id' => $paymentData['payment_method_id'],
                            'type' => 'paid',
                            'amount' => $paymentData['amount'],
                            'date' => now()
                        ]);
                    }
                }
            });

            return response()->json([
                'status' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function excel(Request $request){
        $name = "ReportePagos_".now()->format('dm').".xlsx";
        return Excel::download(new PaymentsExport, $name);
    }
}
