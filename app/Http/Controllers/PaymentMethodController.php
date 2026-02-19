<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function index(Request $request){
        $payment_methods = PaymentMethod::paginate(10);
        return view('payment_methods.index', compact('payment_methods'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:payment_methods,name'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        PaymentMethod::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, PaymentMethod $paymentMethod){
        return response()->json($paymentMethod);
    }

    public function update(Request $request, PaymentMethod $paymentMethod){
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:payment_methods,name,' . $paymentMethod->id
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $paymentMethod->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod){
        // We might want to check for dependencies (sales, expenses, payments)
        // For now, let's keep it simple as per plan.
        $paymentMethod->delete();

        return response()->json([
            'status' => true
        ]);
    }
}
