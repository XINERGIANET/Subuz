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
use Codedge\Fpdf\Fpdf\Fpdf;

class SaleController extends Controller
{
    public function index(Request $request){
        $query = Sale::with(['payment_method', 'client', 'movements']);

        if($request->start_date || $request->end_date){
            $query->when($request->client_id, function($q, $client_id){
                    return $q->where('client_id', $client_id);
                })
                ->when($request->type, function($q, $type){
                    return $q->where('type', $type);
                })
                ->when($request->start_date, function($q, $start_date){
                    return $q->whereDate('date', '>=', $start_date);
                })
                ->when($request->end_date, function($q, $end_date){
                    return $q->whereDate('date', '<=', $end_date);
                });
        }else{
            $query->whereDate('date', now());
        }

        // Totals excluding annulled
        $total_sales = (clone $query)->where('status', '!=', 'Anulado')->sum('total');
        
        // Annulled sales for metrics and modal
        $annulled_sales_query = (clone $query)->where('status', 'Anulado');
        $annulled_count = $annulled_sales_query->count();
        $annulled_sales = $annulled_sales_query->get();

        $sales = $query->latest('date')->paginate(10);

        $payment_methods = PaymentMethod::all();
        $cashbox = Cashbox::currentOpen();

        return view('sales.index', compact('sales', 'total_sales', 'annulled_count', 'annulled_sales', 'payment_methods', 'cashbox'));
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
        $isDelivered = $sale->paid || $sale->type == 'Pago pendiente' || $sale->movements->where('type', 'debt')->isNotEmpty();

        if($isDelivered){
            return response()->json([
                'status' => false,
                'error' => 'No se puede anular una venta que ya ha sido entregada.'
            ]);
        }

        $sale->update(['status' => 'Anulado']);

        return response()->json([
            'status' => true
        ]);
    }

    public function excel(Request $request){
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = "ReporteVentas_".now()->format('dm').".xlsx";
        return Excel::download(new SalesExport($request), $name);
    }

    public function pdf(Request $request){
        $query = Sale::with(['payment_method', 'client'])
            ->when($request->client_id, function($q, $client_id){
                return $q->where('client_id', $client_id);
            })
            ->when($request->type, function($q, $type){
                return $q->where('type', $type);
            })
            ->when($request->start_date, function($q, $start_date){
                return $q->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date, function($q, $end_date){
                return $q->whereDate('date', '<=', $end_date);
            })
            ->when($request->is_pending, function($q){
                return $q->whereIn('type', ['Contado', 'Pago pendiente'])->where('paid', 0);
            })
            ->when($request->is_credit, function($q){
                return $q->where('type', 'Credito')->where('paid', 0);
            });

        if(!$request->start_date && !$request->end_date && !$request->is_pending && !$request->is_credit && !$request->client_id){
            $query->whereDate('date', now());
        }

        $sales = $query->latest('date')->get();

        $fpdf = new Fpdf;
        $fpdf->AddPage();
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }
        
        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('REPORTE DE VENTAS'), 0, 1, 'C');
        
        $period = "Filtro: ";
        if($request->is_pending) $period = "REPORTE DE VENTAS PENDIENTES DE PAGO";
        elseif($request->is_credit) $period = "REPORTE DE CRÉDITOS PENDIENTES";
        else {
            if($request->start_date) $period .= "Desde " . date('d/m/Y', strtotime($request->start_date)) . " ";
            if($request->end_date) $period .= "Hasta " . date('d/m/Y', strtotime($request->end_date));
            if(!$request->start_date && !$request->end_date) $period .= "Ventas de hoy (".now()->format('d/m/Y').")";
        }
        
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(190, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(10);

        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        
        $fpdf->Cell(25, 10, utf8_decode('GUÍA'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('FECHA'), 1, 0, 'C', true);
        $fpdf->Cell(60, 10, utf8_decode('CLIENTE'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('TIPO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('PAGO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('TOTAL'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);
        $total = 0;
        
        foreach($sales as $sale){
            if($sale->status == 'Anulado') continue;

            $fpdf->Cell(25, 8, utf8_decode($sale->guide), 1, 0, 'C');
            $fpdf->Cell(25, 8, $sale->date->format('d/m/Y'), 1, 0, 'C');
            $fpdf->Cell(60, 8, utf8_decode(optional($sale->client)->name ?? 'Consumidor Final'), 1, 0, 'L');
            $fpdf->Cell(30, 8, utf8_decode($sale->type), 1, 0, 'C');
            $fpdf->Cell(25, 8, $sale->paid ? 'SI' : 'NO', 1, 0, 'C');
            $fpdf->Cell(25, 8, 'S/'.number_format($sale->total, 2), 1, 1, 'R');
            $total += $sale->total;
        }

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(165, 10, 'TOTAL EN VENTAS', 1, 0, 'R');
        $fpdf->Cell(25, 10, 'S/'.number_format($total, 2), 1, 1, 'R');

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReporteVentas_".now()->format('dm').".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('D', $name);
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
