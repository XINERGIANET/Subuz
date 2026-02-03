<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Week;
use App\Models\Sale;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentMethod;

class ChargeController extends Controller
{
    public function credit(Request $request){

        $client = null;

        if($request->client_id){
            $client = Client::find($request->client_id);
        }

        $payment_methods = PaymentMethod::all();
        
        $sales = Sale::when($request->client_id, function($query, $client_id){
            return $query->where('client_id', $client_id);
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'Credito')->where('paid', 0)->latest('date');

        $total = $sales->sum('total');
    
        $sales = $sales->paginate(10);

        return view('charges.credit', compact('client', 'sales','payment_methods', 'total'));
    }

    public function pending(Request $request){
        $payment_methods = PaymentMethod::all();
        $total = Sale::where('type', 'Pago pendiente')->where('paid', 0)->sum('total');
        
        $sales = Sale::where([
            ['type', 'Pago pendiente'],
            ['paid', 0]
        ])->latest('date')->paginate(10);

        return view('charges.pending', compact('sales', 'payment_methods', 'total'));
    }

    public function history(Request $request){
        $payments = Payment::with(['sale.client', 'payment_method'])
        ->when($request->client_id, function($query, $client_id){
            return $query->whereHas('sale', function($query) use ($client_id){
                return $query->where('client_id', $client_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date');
        
        $total = $payments->sum('amount');
        
        $payments = $payments->paginate(10);
        
        return view('charges.history', compact('payments', 'total'));
    }
}
