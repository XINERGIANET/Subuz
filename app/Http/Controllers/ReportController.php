<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportLiquidation;
use App\Models\Client;
use App\Models\Week;

class ReportController extends Controller
{
    protected $pdf;

    public function index(){
        return view('reports.index');
    }

    public function liquidation(){
        return view('reports.liquidation');
    }

    public function pdf(Request $request){
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'date' => 'required|date',
            'payment_date' => 'nullable|date',
            'send_mail' => 'nullable|boolean',
        ]);

        $fpdf = new Fpdf;

        $client = Client::find($data['client_id']);
        $week = Week::where([
            ['number', date('W', strtotime($data['date']))],
            ['year', date('Y', strtotime($data['date']))]
        ])->first();

        if(!$client){
            die('El cliente seleccionado no existe');
        }

        if(!$week){
            die('La semana seleccionada no existe');
        }

        $sales = $week->sales()->where([
            ['type', 'Credito'],
            ['client_id', $client->id]
        ])->get();

        $total = $sales->sum('total');
        $paymentDate = $data['payment_date'] ?? $data['date'];

        if(($data['send_mail'] ?? false) && $client->email){
            $request_data = [
                'client_id' => $data['client_id'],
                'date' => $data['date'],
                'payment_date' => $paymentDate
            ];

            Mail::to($client->email)->send(new ReportLiquidation($client, $week, $request_data));
        }

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

        $fpdf->MultiCell(190, 5, utf8_decode('¡Hola '.$client->name.'! queremos entregarte el reporte de liquidación de las compras realizadas del '.$week->start_date->format('d/m/Y').' al cierre de la semana '.$week->end_date->format('d/m/Y').'.'));

        $fpdf->Ln(10);
        
        $fpdf->SetFont('Montserrat', 'B', 14);
        $fpdf->SetTextColor(255,255,255);
        $fpdf->Cell(190, 15, utf8_decode('REPORTE DE LIQUIDACIÓN'),0,0,'C',1);
        
        $fpdf->Ln(15);

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

        $fpdf->Cell(45, 5, date('d/m/Y', strtotime($paymentDate)),0,0,'C');

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
            $fpdf->Cell(170, 8, utf8_decode('GUÍA '.$sale->guide.' - '.$sale->date->format('d/m/Y')),0,0,'L',1);
            $fpdf->Ln();

            foreach($sale->details as $detail){

                $fpdf->Cell(20, 8);
                $fpdf->Cell(80, 8, utf8_decode($detail->product->name));
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


        $name = 'Liquidacion_'.str_replace(" ", "_", $client->name)."_".now()->format('dm').".pdf";

        $fpdf->Output('D', $name);

    }

}
