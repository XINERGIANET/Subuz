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

    public function liquidationsHistory(){
        $liquidations = \App\Models\Liquidation::with('client')->latest()->paginate(15);
        return view('reports.liquidations_history', compact('liquidations'));
    }

    public function cashbox(Request $request){
        $start_date = $request->start_date ?: ($request->date ?: now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->end_date ?: ($request->date ?: now()->format('Y-m-d'));

        $query = Cashbox::with(['movements', 'openedBy', 'closedBy', 'movements.user', 'movements.payment_method']);

        if ($request->filled('start_date') || $request->filled('end_date') || $request->filled('date')) {
            if ($request->filled('date') && !$request->filled('start_date') && !$request->filled('end_date')) {
                $query->where(function($q) use ($request) {
                    $q->whereDate('opened_at', $request->date)
                      ->orWhereDate('closed_at', $request->date);
                });
            } else {
                if ($start_date) {
                    $query->whereDate('opened_at', '>=', $start_date);
                }
                if ($end_date) {
                    $query->whereDate('opened_at', '<=', $end_date);
                }
            }
        } else {
            // Por defecto: mostrar cajas del mes actual
            $query->whereDate('opened_at', '>=', now()->startOfMonth()->format('Y-m-d'));
        }

        $cashboxes = $query->orderBy('opened_at', 'desc')->get();

        foreach($cashboxes as $cb){
            $start = $cb->opened_at;
            $end = $cb->is_open ? now() : $cb->closed_at;
            
            $cb->expenses_list = \App\Models\Expense::whereBetween('date', [$start, $end])
                ->with(['payment_method', 'user'])
                ->get();
        }

        $payment_methods = \App\Models\PaymentMethod::all();

        return view('reports.cashbox', compact('cashboxes', 'start_date', 'end_date', 'payment_methods'));
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

        \App\Models\Liquidation::create([
            'client_id' => $client->id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'payment_date' => $request->payment_date,
            'correlative_type' => $request->correlative_type,
            'general_correlative' => $request->general_correlative,
            'sale_correlatives' => $request->sale_correlatives,
            'total' => $total
        ]);

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

        $fpdf->Ln(5);

        // Fetch accounts marked for liquidation reports
        $accountsForReport = \App\Models\PaymentMethod::where('show_in_liquidation_reports', 1)->get();
        if ($accountsForReport->count() > 0) {
            $fpdf->SetFont('Montserrat', 'B', 10);
            $fpdf->SetTextColor(2, 93, 166);
            $fpdf->Cell(190, 6, utf8_decode('Cuentas para transferencias:'), 0, 1, 'C');
            $fpdf->SetFont('Montserrat', '', 9);
            $fpdf->SetTextColor(80, 80, 80);
            $accountsStr = '';
            foreach ($accountsForReport as $acc) {
                $accInfo = $acc->name;
                if ($acc->account_number) $accInfo .= ': ' . $acc->account_number;
                if ($acc->holder_name) $accInfo .= ' (' . $acc->holder_name . ')';
                $accountsStr .= $accInfo . '   |   ';
            }
            $accountsStr = rtrim($accountsStr, '   |   ');
            $fpdf->MultiCell(190, 5, utf8_decode($accountsStr), 0, 'C');
            $fpdf->Ln(5);
        } else {
            $fpdf->Ln(5);
        }
        
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
        $product_totals = [];
        
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
            
                if(!isset($product_totals[$product_name])){
                    $product_totals[$product_name] = [
                        'quantity' => 0,
                        'subtotal' => 0
                    ];
                }
                $product_totals[$product_name]['quantity'] += $detail->quantity;
                $product_totals[$product_name]['subtotal'] += ($detail->price * $detail->quantity);
            }

            $i++;

        }

        $fpdf->Ln(5);

        // Header for summary
        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(255,255,255);
        $fpdf->SetFillColor(2,93,166);
        $fpdf->Cell(130, 8, utf8_decode('RESUMEN POR PRODUCTO'),0,0,'C',1);
        $fpdf->Cell(30, 8, utf8_decode('CANTIDAD'),0,0,'C',1);
        $fpdf->Cell(30, 8, utf8_decode('SUBTOTAL'),0,0,'C',1);
        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(0,0,0);

        foreach($product_totals as $name => $totals) {
            $fpdf->Cell(130, 8, utf8_decode($name), 'B', 0, 'L');
            $fpdf->Cell(30, 8, $totals['quantity'], 'B', 0, 'C');
            $fpdf->Cell(30, 8, 'S/'.number_format($totals['subtotal'], 2), 'B', 0, 'C');
            $fpdf->Ln();
        }

        $fpdf->Ln(5);

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->Cell(130, 8);
        $fpdf->Cell(30, 8, 'TOTAL GENERAL',0,0,'C');
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
        $month = $request->get('month', now()->format('m'));
        $year = $request->get('year', now()->format('Y'));

        $query = \App\Models\SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select('products.name', \Illuminate\Support\Facades\DB::raw('SUM(sale_details.quantity) as total_quantity'), \Illuminate\Support\Facades\DB::raw('SUM(sale_details.quantity * sale_details.price) as total_sales_amount'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc');

        if ($period == 'day') {
            $query->whereDate('sales.date', now()->format('Y-m-d'));
        } elseif ($period == 'month') {
            $query->whereMonth('sales.date', $month)
                  ->whereYear('sales.date', $year);
        } elseif ($period == 'year') {
            $query->whereYear('sales.date', $year);
        } elseif ($period == 'custom') {
            if ($start_date && $end_date) {
                $query->whereBetween('sales.date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
        }

        $data = $query->get();

        return view('reports.products', compact('data', 'period', 'start_date', 'end_date', 'month', 'year'));
    }

    public function cashboxPdf(Request $request, $cashbox_id)
    {
        $cb = Cashbox::with(['movements.user', 'movements.payment_method', 'movements.sale.client', 'openedBy', 'closedBy'])->findOrFail($cashbox_id);
        
        $start = $cb->opened_at;
        $end = $cb->is_open ? now() : $cb->closed_at;
        
        $expenses = \App\Models\Expense::whereBetween('date', [$start, $end])
            ->with(['payment_method', 'user'])
            ->get();

        $payment_methods = \App\Models\PaymentMethod::all();
        $opening_balances = is_string($cb->opening_balances) ? json_decode($cb->opening_balances, true) : ($cb->opening_balances ?? []);
        $closing_balances = is_string($cb->closing_balances) ? json_decode($cb->closing_balances, true) : ($cb->closing_balances ?? []);

        // Agrupación de ingresos por usuario
        $income_by_user = [];
        foreach ($cb->movements as $m) {
            if ($m->type == 'paid' || $m->type == 'income') {
                $userName = optional($m->user)->name ?? (optional($cb->openedBy)->name ?? 'Sistema');
                if (!isset($income_by_user[$userName])) {
                    $income_by_user[$userName] = 0;
                }
                $income_by_user[$userName] += (float)$m->amount;
            }
        }

        // Agrupación de gastos por usuario
        $expense_by_user = [];
        foreach ($expenses as $e) {
            $userName = optional($e->user)->name ?? (optional($cb->openedBy)->name ?? 'Sistema');
            if (!isset($expense_by_user[$userName])) {
                $expense_by_user[$userName] = 0;
            }
            $expense_by_user[$userName] += (float)$e->amount;
        }

        $total_incomes = array_sum($income_by_user);
        $total_expenses = array_sum($expense_by_user);

        // PDF Generation via FPDF
        $fpdf = new Fpdf('P', 'mm', 'A4');
        $fpdf->AddPage();
        $fpdf->SetAutoPageBreak(true, 15);

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        // Logo
        if (file_exists(public_path('assets/images/logo.jpg'))) {
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 28);
        }

        // Header Title
        $fpdf->SetFont('Montserrat', 'B', 14);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 8, utf8_decode('REPORTE RESUMEN DE CAJA'), 0, 1, 'C');
        
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->SetTextColor(100, 100, 100);
        $statusStr = $cb->is_open ? 'SESION ABIERTA (EN CURSO)' : 'SESION CERRADA #' . $cb->id;
        $fpdf->Cell(190, 5, utf8_decode($statusStr), 0, 1, 'C');
        $fpdf->Ln(4);

        // Info Block (Apertura y Cierre)
        $fpdf->SetFillColor(245, 247, 250);
        $fpdf->Rect(10, $fpdf->GetY(), 190, 18, 'F');
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->SetTextColor(50, 50, 50);

        $fpdf->SetX(12);
        $fpdf->Cell(90, 5, utf8_decode('APERTURA: ') . ($cb->opened_at ? $cb->opened_at->format('d/m/Y H:i') : '-') . ' (' . utf8_decode(optional($cb->openedBy)->name ?? 'Sistema') . ')', 0, 0);
        $fpdf->Cell(95, 5, utf8_decode('CIERRE: ') . ($cb->closed_at ? $cb->closed_at->format('d/m/Y H:i') : ($cb->is_open ? 'En curso' : '-')) . ' (' . utf8_decode(optional($cb->closedBy)->name ?? 'Sistema') . ')', 0, 1);

        $fpdf->SetX(12);
        $fpdf->Cell(90, 5, utf8_decode('TOTAL INGRESOS TURNO: S/ ') . number_format($total_incomes, 2), 0, 0);
        $fpdf->Cell(95, 5, utf8_decode('TOTAL GASTOS TURNO: S/ ') . number_format($total_expenses, 2), 0, 1);
        $fpdf->Ln(6);

        // 1. SALDOS INICIALES Y FINALES (EFECTIVO Y BANCOS)
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->Cell(190, 7, utf8_decode('1. SALDO INICIAL Y FINAL EN EFECTIVO Y BANCOS'), 0, 1, 'L', true);

        $fpdf->SetFillColor(230, 235, 245);
        $fpdf->SetTextColor(30, 30, 30);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(70, 6, utf8_decode('CUENTA / MÉTODO DE PAGO'), 1, 0, 'L', true);
        $fpdf->Cell(40, 6, utf8_decode('SALDO INICIAL'), 1, 0, 'C', true);
        $fpdf->Cell(40, 6, utf8_decode('SALDO FINAL / CIERRE'), 1, 0, 'C', true);
        $fpdf->Cell(40, 6, utf8_decode('VARIACIÓN'), 1, 1, 'C', true);

        $fpdf->SetFont('Montserrat', '', 8);
        $sum_initial = 0;
        $sum_final = 0;

        foreach ($payment_methods as $pm) {
            $init = ($pm->id == 1) ? (float)$cb->opening_amount : (float)($opening_balances[$pm->id] ?? 0);
            $fin = ($pm->id == 1) ? (float)$cb->closing_amount : (float)($closing_balances[$pm->id] ?? 0);
            
            // Si está abierta, calcular saldo final actual
            if ($cb->is_open) {
                $p_movs = $cb->movements->where('payment_method_id', $pm->id);
                $p_exp = $expenses->where('payment_method_id', $pm->id)->sum('amount');
                $fin = $init + $p_movs->where('type', 'paid')->sum('amount') + $p_movs->where('type', 'income')->sum('amount') + $p_movs->where('type', 'transfer')->sum('amount') - $p_exp;
            }

            $diff = $fin - $init;
            $sum_initial += $init;
            $sum_final += $fin;

            if ($init > 0 || $fin > 0 || $pm->id == 1) {
                $fpdf->Cell(70, 5, '  ' . utf8_decode($pm->name), 1, 0, 'L');
                $fpdf->Cell(40, 5, 'S/ ' . number_format($init, 2), 1, 0, 'R');
                $fpdf->Cell(40, 5, 'S/ ' . number_format($fin, 2), 1, 0, 'R');
                $fpdf->Cell(40, 5, ($diff >= 0 ? '+' : '') . 'S/ ' . number_format($diff, 2), 1, 1, 'R');
            }
        }

        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->SetFillColor(240, 240, 240);
        $fpdf->Cell(70, 6, '  TOTALES', 1, 0, 'L', true);
        $fpdf->Cell(40, 6, 'S/ ' . number_format($sum_initial, 2), 1, 0, 'R', true);
        $fpdf->Cell(40, 6, 'S/ ' . number_format($sum_final, 2), 1, 0, 'R', true);
        $fpdf->Cell(40, 6, (($sum_final - $sum_initial) >= 0 ? '+' : '') . 'S/ ' . number_format($sum_final - $sum_initial, 2), 1, 1, 'R', true);
        $fpdf->Ln(5);

        // 2. INGRESOS Y GASTOS POR USUARIO
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->Cell(92, 7, utf8_decode('2. TOTAL INGRESOS POR USUARIO'), 0, 0, 'L', true);
        $fpdf->Cell(6, 7, '', 0, 0);
        $fpdf->Cell(92, 7, utf8_decode('3. TOTAL GASTOS POR USUARIO'), 0, 1, 'L', true);

        // Two side-by-side columns
        $y_start = $fpdf->GetY();

        // Left: Incomes by user
        $fpdf->SetFillColor(230, 235, 245);
        $fpdf->SetTextColor(30, 30, 30);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(57, 6, utf8_decode('USUARIO'), 1, 0, 'L', true);
        $fpdf->Cell(35, 6, utf8_decode('MONTO TOTAL'), 1, 1, 'C', true);

        $fpdf->SetFont('Montserrat', '', 8);
        if (count($income_by_user) > 0) {
            foreach ($income_by_user as $uName => $uAmount) {
                $fpdf->Cell(57, 5, '  ' . utf8_decode(substr($uName, 0, 26)), 1, 0, 'L');
                $fpdf->Cell(35, 5, 'S/ ' . number_format($uAmount, 2), 1, 1, 'R');
            }
        } else {
            $fpdf->Cell(92, 5, utf8_decode('  Sin ingresos en el turno'), 1, 1, 'L');
        }
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(57, 6, '  TOTAL INGRESOS', 1, 0, 'L', true);
        $fpdf->Cell(35, 6, 'S/ ' . number_format($total_incomes, 2), 1, 1, 'R', true);
        $y_left_end = $fpdf->GetY();

        // Right: Expenses by user
        $fpdf->SetXY(108, $y_start);
        $fpdf->SetFillColor(250, 230, 230);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(57, 6, utf8_decode('USUARIO'), 1, 0, 'L', true);
        $fpdf->Cell(35, 6, utf8_decode('MONTO TOTAL'), 1, 1, 'C', true);

        $fpdf->SetFont('Montserrat', '', 8);
        if (count($expense_by_user) > 0) {
            foreach ($expense_by_user as $uName => $uAmount) {
                $fpdf->SetX(108);
                $fpdf->Cell(57, 5, '  ' . utf8_decode(substr($uName, 0, 26)), 1, 0, 'L');
                $fpdf->Cell(35, 5, 'S/ ' . number_format($uAmount, 2), 1, 1, 'R');
            }
        } else {
            $fpdf->SetX(108);
            $fpdf->Cell(92, 5, utf8_decode('  Sin gastos en el turno'), 1, 1, 'L');
        }
        $fpdf->SetX(108);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(57, 6, '  TOTAL GASTOS', 1, 0, 'L', true);
        $fpdf->Cell(35, 6, 'S/ ' . number_format($total_expenses, 2), 1, 1, 'R', true);
        $y_right_end = $fpdf->GetY();

        $fpdf->SetY(max($y_left_end, $y_right_end) + 5);

        // 4. OBSERVACIONES (Permite observaciones del usuario o de cierre)
        $observation = $request->observation ?: ($cb->note ?: '');

        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->Cell(190, 7, utf8_decode('4. OBSERVACIONES Y NOTAS DE CUADRE'), 0, 1, 'L', true);

        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->SetTextColor(40, 40, 40);
        $fpdf->SetFillColor(250, 250, 250);

        if (!empty($observation)) {
            $fpdf->MultiCell(190, 5, utf8_decode($observation), 1, 'L', true);
        } else {
            $fpdf->Cell(190, 14, utf8_decode('  Sin observaciones registradas.'), 1, 1, 'L', true);
        }
        $fpdf->Ln(8);

        // Firmas
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(60, 5, '___________________________', 0, 0, 'C');
        $fpdf->Cell(70, 5, '', 0, 0, 'C');
        $fpdf->Cell(60, 5, '___________________________', 0, 1, 'C');

        $fpdf->Cell(60, 4, utf8_decode('Entregado por: ' . (optional($cb->openedBy)->name ?? 'Cajero')), 0, 0, 'C');
        $fpdf->Cell(70, 4, '', 0, 0, 'C');
        $fpdf->Cell(60, 4, utf8_decode('Revisado / Administración'), 0, 1, 'C');

        $fpdf->Ln(4);
        $fpdf->SetFont('Montserrat', '', 7);
        $fpdf->Cell(190, 4, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i:s') . ' | Subuz Sistema de Gestión'), 0, 1, 'R');

        $filename = "Resumen_Caja_" . $cb->id . "_" . ($cb->opened_at ? $cb->opened_at->format('dmY') : now()->format('dmY')) . ".pdf";

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fpdf->Output('I', $filename);
    }
}

