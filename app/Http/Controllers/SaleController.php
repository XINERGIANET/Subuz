<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\Week;
use App\Models\Payment;
use App\Models\Cashbox;
use App\Models\CashboxMovement;

class SaleController extends Controller
{
    public function index(Request $request){
        if($request->start_date || $request->end_date){
            $sales = Sale::with(['payment_method', 'client', 'movements'])
                ->when($request->client_id, function($query, $client_id){
                    return $query->where('client_id', $client_id);
                })
                ->when($request->type, function($query, $type){
                    return $query->where('type', $type);
                })
                ->when($request->start_date, function($query, $start_date){
                    return $query->whereDate('date', '>=', $start_date);
                })
                ->when($request->end_date, function($query, $end_date){
                    return $query->whereDate('date', '<=', $end_date);
                })
                ->latest('date');
        }else{
            $sales = Sale::with(['payment_method', 'client'])
                ->whereDate('date', now())
                ->latest('date');
        }
        
        $total_sales = $sales->sum('total');

        $sales = $sales->paginate(10);

        $payment_methods = PaymentMethod::all();
        $cashbox = Cashbox::currentOpen();

        return view('sales.index', compact('sales', 'total_sales', 'payment_methods', 'cashbox'));
    }

    public function create(){
        $sale_count = DB::table('settings')->pluck('sale_count')->first();
        $order = 'V'.str_pad($sale_count + 1, 4, "0", STR_PAD_LEFT);
        $products = Product::all();
        return view('sales.create', compact('order', 'products'));
    }

    public function store(Request $request){

        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];

        $request->merge(['guide' => 'GR-'.str_pad($request->guide, 5, "0", STR_PAD_LEFT)]);

        $validator = Validator::make($request->all(), [
            'guide' => 'required|unique:sales',
            'type' => 'required|in:Contado,Credito',
            'date' => 'required|date',
            'client_id' => 'required'
        ]);

        $validator->after(function($validator) use ($cart){

            if(count($cart['items']) == 0){
                $validator->errors()->add('cart', 'Debe agregar por lo menos 1 producto');
            }

        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $sale_count = DB::table('settings')->pluck('sale_count')->first();
        $order = 'V'.str_pad($sale_count + 1, 4, "0", STR_PAD_LEFT);

        $week = Week::where('number', now()->format('W'))->first();

        if(!$week){
            $number = now()->format('W');
            $year = now()->format('Y');
            $start_date = date('Y-m-d', strtotime("{$year}W{$number}"));
            $end_date = date('Y-m-d', strtotime("{$year}W{$number} +6 days"));
            $week = Week::create([
                'number' => $number,
                'year' => $year,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
        }
        
        $sale = Sale::create([
            'order' => $order,
            'date' => $request->date.' '.now()->format('H:i:s'),
            'week_id' => $week->id,
            'guide' => $request->guide,
            'type' => $request->type,
            'payment_method_id' => null,
            'client_id' => $request->client_id,
            'total' => $cart['total'],
            'debt' => $request->type == 'Credito' ? $cart['total'] : 0,
            'paid' => 0
        ]);

        foreach($cart['items'] as $item){
            SaleDetail::create([
                'sale_id' => $sale->id,
                'product_id' => $item['id'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'special' => $item['special'] ? 1 : 0,
            ]);
        }

        DB::table('settings')->update([
            'sale_count' => $sale_count + 1
        ]);

        session()->forget('cart');

        return response()->json(['status' => true]);
    }

    public function details(Request $request, Sale $sale){
        
        if(!$sale){
            return response()->json([
                'status' => false
            ]);
        }

        return response()->json([
            'status' => true,
            'details' => optional($sale)->details()->with('product')->get()
        ]);
    }

    public function edit(Request $request, Sale $sale){
        return response()->json([
            'status' => true,
            'id' => $sale->id,
            'date' => optional($sale->date)->format('Y-m-d'),
            'details' => optional($sale)->details()->with('product')->get()
        ]);
    }

    public function update(Request $request, Sale $sale){
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'details.id.*' => 'required|integer',
            'details.price.*' => 'required|numeric',
            'details.quantity.*' => 'required|integer'
        ]);

        $total = 0;

        $details = $request->details;

        foreach($details['id'] as $key => $value){

            $detail = SaleDetail::findOrFail($value);

            $price = $details['price'][$key];
            $quantity = $details['quantity'][$key];
            
            $detail->update([
                'price' => $price,
                'quantity' => $quantity
            ]);

            $total += floatval($price) * intval($quantity);
        }

        $sale->update([
            'date' => $request->date,
            'total' => $total
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => 'El formulario no ha sido validado'
            ]);
        }
        
        return response()->json(['status' => true]);
    }

    public function destroy(Request $request, Sale $sale){

        $status = true;

        if($sale->type == 'Credito'){
            $payments = Payment::where('week_id', $sale->week_id)
                ->where('client_id', $sale->client_id)->get();

            if($payments->count() > 0){
                $status = false;
            }else{
                $sale->delete();
            }
        }else{
            $sale->delete();
        }

        return response()->json([
            'status' => $status
        ]);
    }

    public function excel(Request $request){
        $name = "ReporteVentas_".now()->format('dm').".xlsx";
        return Excel::download(new SalesExport, $name);
    }

    public function markDispatch(Request $request, Sale $sale){

        if(!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('despachador')){
            return response()->json([
                'status' => false,
                'error' => 'No autorizado'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'paid' => 'required|boolean'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        if($sale->paid){
            return response()->json([
                'status' => false,
                'error' => 'La venta ya esta marcada como pagada.'
            ]);
        }

        $cashbox = Cashbox::currentOpen();

        if(!$cashbox){
            return response()->json([
                'status' => false,
                'error' => 'Debe aperturar caja antes de registrar el despacho.'
            ]);
        }

        DB::transaction(function() use ($request, $sale, $cashbox){

            if($request->paid){
                
                $payments = $request->payments;
                $totalPaid = 0;
                
                if(!$payments || count($payments) == 0){
                    throw new \Exception("Debe agregar por lo menos un método de pago.");
                }

                foreach($payments as $payment){
                    if(!isset($payment['method_id']) || !isset($payment['amount'])){
                        throw new \Exception("Datos de pago incompletos.");
                    }
                    $totalPaid += floatval($payment['amount']);
                }

                if(abs($totalPaid - floatval($sale->total)) > 0.01){
                    throw new \Exception("La suma de los pagos (S/".number_format($totalPaid, 2).") debe ser igual al total de la venta (S/".number_format($sale->total, 2).").");
                }

                // Update sale as paid
                $sale->update([
                    'type' => 'Contado',
                    'payment_method_id' => $payments[0]['method_id'], // Use first as primary reference
                    'debt' => 0,
                    'paid' => 1
                ]);

                // Register all movements and payments
                foreach($payments as $payment){
                    Payment::create([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $payment['method_id'],
                        'amount' => $payment['amount'],
                        'date' => now()
                    ]);

                    CashboxMovement::create([
                        'cashbox_id' => $cashbox->id,
                        'sale_id' => $sale->id,
                        'user_id' => auth()->id(),
                        'payment_method_id' => $payment['method_id'],
                        'type' => 'paid',
                        'amount' => $payment['amount'],
                        'date' => now()
                    ]);
                }

            }else{

                if(!auth()->user()->hasRole('despachador')){
                    throw new \Exception("Solo el despachador puede marcar pendiente de pago.");
                }

                $sale->update([
                    'type' => $sale->type,
                    'payment_method_id' => null,
                    'debt' => $sale->total,
                    'paid' => 0
                ]);

                CashboxMovement::create([
                    'cashbox_id' => $cashbox->id,
                    'sale_id' => $sale->id,
                    'user_id' => auth()->id(),
                    'payment_method_id' => null,
                    'type' => 'debt',
                    'amount' => $sale->total,
                    'date' => now()
                ]);
            }
        });

        return response()->json([
            'status' => true
        ]);
    }

    public function updateDeliveryStatus(Request $request, Sale $sale)
    {
        $status = $request->status; // 1 = Entregado, 0 = No entregado

        if ($status == 1) {
            // Confirm delivery without payment (mark as Pago pendiente)
            return $this->markDispatch($request->merge(['paid' => 0]), $sale);
        } else {
            // Revert delivery (return to Credito)
            DB::transaction(function() use ($sale) {
                // Delete debt movement if it exists
                CashboxMovement::where('sale_id', $sale->id)
                    ->where('type', 'debt')
                    ->delete();
                
                $sale->update([
                    'type' => $sale->type,
                    'paid' => 0,
                    'debt' => $sale->total,
                    'payment_method_id' => null
                ]);
            });

            return response()->json(['status' => true]);
        }
    }
}
