<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportLiquidation;
use App\Models\Client;
use App\Models\Sale;
use App\Models\Cashbox;

class ReportController extends Controller
{
    protected $pdf;

    public function index(){
        return view('reports.index');
    }

    public function liquidation(){
        return view('reports.liquidation');
    }

    public function cashbox(Request $request){
        $date = $request->date ? $request->date : now()->format('Y-m-d');
        
        $cashboxes = Cashbox::with(['movements', 'openedBy', 'closedBy', 'movements.user', 'movements.payment_method'])
            ->whereDate('opened_at', $date)
            ->orWhereDate('closed_at', $date)
            ->get();

        foreach($cashboxes as $cb){
            $start = $cb->opened_at;
            $end = $cb->is_open ? now() : $cb->closed_at;
            
            $cb->expenses_list = \App\Models\Expense::whereBetween('date', [$start, $end])
                ->with('payment_method')
                ->get();
        }

        return view('reports.cashbox', compact('cashboxes', 'date'));
    }

    public function getSalesForLiquidation(Request $request){
        $client_id = $request->client_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $sales = Sale::where([
            ['type', 'Credito'],
            ['client_id', $client_id]
        ])->whereBetween('date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])->get();

        $data = $sales->map(function($sale){
            return [
                'id' => $sale->id,
                'guide' => $sale->guide,
                'date' => $sale->date->format('d/m/Y'),
                'total' => number_format($sale->total, 2)
            ];
        });

        return response()->json(['status' => true, 'sales' => $data]);
    }

    public function pdf(Request $request){
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_date' => 'nullable|date',
            'send_mail' => 'nullable|boolean',
            'correlative_type' => 'nullable|string|in:general,per_sale',
            'general_correlative' => 'nullable|string',
            'sale_correlatives' => 'nullable|array'
        ]);

        $fpdf = new Fpdf;

        $client = Client::find($data['client_id']);

        if(!$client){
            return redirect()->back()->with('error', 'El cliente seleccionado no existe');
        }

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $sales = Sale::where([
            ['type', 'Credito'],
            ['client_id', $client->id]
        ])->whereBetween('date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])->get();

        if($sales->count() == 0){
            return redirect()->back()->with('error', 'No existen registros de ventas');
        }

        $total = $sales->sum('total');
        $paymentDate = $data['payment_date'] ?? $end_date;

        $fpdf->AddPage();
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        $fpdf->SetFillColor(2,93,166);
        $fpdf->SetDrawColor(2,93,166);
        $fpdf->SetLineWidth(0.4);

        $fpdf->Image(public_path('assets/images/logo.jpg'), 160,10,30); 
        
        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(60, 5, utf8_decode('SUBUZ S.A.C.'),0,1);
        $fpdf->Cell(60, 5, utf8_decode('Contacto: 920488526 // 920381594'),0,1);
        
        $fpdf->Ln(5);

        $months = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','setiembre','octubre','noviembre','diciembre'];

        $day = now()->format('d');
        $month = $months[now()->format('n')];
        $year = now()->format('Y');

        $fpdf->Cell(190, 10, "Chiclayo, {$day} de {$month} de {$year}",0,0,'R');

        $fpdf->Ln(15);

        if($start_date && $end_date){
            $fpdf->MultiCell(190, 5, utf8_decode('¡Hola '.$client->name.'! queremos entregarte el reporte de liquidación de las compras realizadas del '.date('d/m/Y', strtotime($start_date)).' al '.date('d/m/Y', strtotime($end_date)).'.'));
        }else{
            $fpdf->MultiCell(190, 5, utf8_decode('¡Hola '.$client->name.'! queremos entregarte el reporte de liquidación de las compras realizadas.'));
        }

        $fpdf->Ln(10);
        
        $fpdf->SetFont('Montserrat', 'B', 14);
        $fpdf->SetTextColor(255,255,255);
        $fpdf->Cell(190, 15, utf8_decode('REPORTE DE LIQUIDACIÓN'),0,0,'C',1);
        $fpdf->Ln(15);
        
        if ($request->correlative_type == 'general' && !empty($request->general_correlative)) {
            $fpdf->SetFont('Montserrat', 'B', 12);
            $fpdf->SetTextColor(2,93,166);
            $fpdf->Cell(190, 8, utf8_decode('Comprobante: ' . $request->general_correlative), 0, 1, 'C');
            $fpdf->Ln(5);
        }

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2,93,166);

        $fpdf->Cell(100, 10, 'CLIENTE', 'B');

        $fpdf->Cell(45, 10, 'FECHA DE PAGO', 'B',0,'C');

        $fpdf->Cell(45, 10, 'MONTO TOTAL', 'B',0,'C');
        
        $fpdf->Ln(15);
        
        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->SetTextColor(0,0,0);

        $current_x = $fpdf->GetX();
        $current_y = $fpdf->GetY();

        $cell_width = 100;

        $fpdf->MultiCell($cell_width, 5, utf8_decode($client->name));

        $fpdf->SetXY($current_x + $cell_width, $current_y);

        $fpdf->Cell(45, 5, $request->payment_date ? date('d/m/Y', strtotime($request->payment_date)) : '',0,0,'C');

        $fpdf->Cell(45, 5, 'S/'.number_format($total, 2),0,0,'C');

        $fpdf->Ln(20);

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(255,255,255);
        $fpdf->Cell(20, 10, utf8_decode('ITEM'),0,0,'C',1);
        $fpdf->Cell(80, 10, utf8_decode('PRODUCTO'),0,0,'C',1);
        $fpdf->Cell(30, 10, utf8_decode('P. UNIT.'),0,0,'C',1);
        $fpdf->Cell(30, 10, utf8_decode('CANTIDAD'),0,0,'C',1);
        $fpdf->Cell(30, 10, utf8_decode('SUBTOTAL'),0,0,'C',1);
        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(0,0,0);

        $i = 1;
        
        foreach($sales as $sale){

            $fpdf->SetFillColor(200,200,200);
            $fpdf->Cell(20, 8, $i,0,0,'C',1);
            $guide_text = 'GUÍA '.$sale->guide.' - '.$sale->date->format('d/m/Y');
            if ($request->correlative_type == 'per_sale' && isset($request->sale_correlatives[$sale->id]) && !empty($request->sale_correlatives[$sale->id])) {
                $guide_text .= ' - Comp: ' . $request->sale_correlatives[$sale->id];
            }
            $fpdf->Cell(170, 8, utf8_decode($guide_text),0,0,'L',1);
            $fpdf->Ln();

            foreach($sale->details as $detail){

                $fpdf->Cell(20, 8);
                $product_name = $detail->product ? $detail->product->name : 'Producto eliminado';
                $fpdf->Cell(80, 8, utf8_decode($product_name));
                $fpdf->Cell(30, 8, 'S/'.$detail->price,0,0,'C');
                $fpdf->Cell(30, 8, $detail->quantity,0,0,'C');
                $fpdf->Cell(30, 8, 'S/'.number_format($detail->price * $detail->quantity, 2),0,0,'C');
                $fpdf->Ln();
            
            }

            $i++;

        }

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->Cell(130, 8);
        $fpdf->Cell(30, 8, 'TOTAL',0,0,'C');
        $fpdf->Cell(30, 8, 'S/'.number_format($total, 2),0,0,'C');

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(190, 10, 'Gracias por ser nuestro cliente');



        
        if($request->send_mail && $client->email){
            $request_data = [
                'client_id' => $request->client_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'payment_date' => $request->payment_date
            ];
            
            Mail::to($client->email)->send(new ReportLiquidation($client, $request_data));
        }

        $name = 'Liquidacion_'.str_replace(" ", "_", $client->name)."_".now()->format('dm').".pdf";

        $fpdf->Output('D', $name);

    }

    public function products(Request $request){
        $period = $request->get('period', 'day'); // day, month, year, custom
        $start_date = $request->get('start_date', now()->format('Y-m-d'));
        $end_date = $request->get('end_date', now()->format('Y-m-d'));

        $query = \App\Models\SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select('products.name', \Illuminate\Support\Facades\DB::raw('SUM(sale_details.quantity) as total_quantity'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc');

        if ($period == 'day') {
            $query->whereDate('sales.date', now()->format('Y-m-d'));
        } elseif ($period == 'month') {
            $query->whereMonth('sales.date', now()->format('m'))
                  ->whereYear('sales.date', now()->format('Y'));
        } elseif ($period == 'year') {
            $query->whereYear('sales.date', now()->format('Y'));
        } elseif ($period == 'custom') {
            if ($start_date && $end_date) {
                $query->whereBetween('sales.date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
        }

        $data = $query->get();

        return view('reports.products', compact('data', 'period', 'start_date', 'end_date'));
    }

}
