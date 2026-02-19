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

                }elseif($request->type == 'Pago pendiente'){
                    
                    // For pending payment, we usually expect full payment. 
                    // If multiple payments were sent (which shouldn't happen based on current UI for pending), handle it?
                    // But legacy UI sends single payment_method_id. 
                    // Let's assume we use the first payment method if multiple are sent, 
                    // OR distribute the total if logic required, but for now specific logic for Pending:
                    
                    // The standard pending payment workflow pays the ENTIRE total.
                    // The request->amount is usually ignored or overwritten by total.
                    
                    // However, if we want to allow split payment for Pending too later, 
                    // we'd need to change this logic. But for now, user asked for "Cobranza de Crédito".
                    // So let's keep Pending as "Pay Full Amount" using the first method provided.

                    $paymentMethodId = $request->payments[0]['payment_method_id']; // Use first method

                    $sale->update([
                        'debt' => 0,
                        'paid' => 1
                    ]);

                    Payment::create([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $sale->total,
                        'date' => now()
                    ]);

                    CashboxMovement::create([
                        'cashbox_id' => $cashbox->id,
                        'sale_id' => $sale->id,
                        'user_id' => auth()->id(),
                        'payment_method_id' => $paymentMethodId,
                        'type' => 'paid',
                        'amount' => $sale->total,
                        'date' => now()
                    ]);
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
