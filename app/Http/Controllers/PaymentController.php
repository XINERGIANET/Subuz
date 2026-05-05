<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;
use App\Models\Week;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Cashbox;
use App\Models\CashboxMovement;
use Codedge\Fpdf\Fpdf\Fpdf;

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
            'payments' => 'required|array',
            'payments.*.payment_method_id' => 'required',
            'type' => 'required',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:51200'
        ]);

        if(!$request->sale_id && !$request->sale_ids){
            return response()->json([
                'status' => false,
                'error' => 'Debe seleccionar al menos una venta.'
            ]);
        }

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

        try {
            $saleIds = $request->sale_ids ?? [$request->sale_id];
            $sales = Sale::whereIn('id', $saleIds)->orderBy('date', 'asc')->get();

            // Handle Photo Upload (using first sale id for filename)
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = 'payment_' . $sales->first()->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $photoPath = $file->storeAs('dispatches', $filename, 'public');
            }

            DB::transaction(function() use ($request, $sales, $cashbox, $photoPath){
                
                $totalAmount = floatval(collect($request->payments)->sum('amount'));
                
                if($request->type == 'Credito'){
                    $totalDebt = $sales->sum('debt');
                    // Check if total amount exceeds debt (with small tolerance for float issues)
                    if(round($totalAmount, 2) > round($totalDebt, 2)){
                        throw new \Exception('El monto total a pagar supera la deuda actual.');
                    }
                } elseif($request->type == 'Pago pendiente' || $request->type == 'Contado'){
                    $totalSalesAmount = $sales->sum('total');
                    // For pending/cash, we expect the FULL amount to be paid to clear it.
                    if(abs($totalAmount - floatval($totalSalesAmount)) > 0.01){
                        throw new \Exception("La suma de los pagos (S/".number_format($totalAmount, 2).") debe ser igual al total de las ventas (S/".number_format($totalSalesAmount, 2).").");
                    }
                }

                // Flatten all payment methods into a pool of funds
                $funds = [];
                foreach($request->payments as $p){
                    $funds[] = [
                        'payment_method_id' => $p['payment_method_id'],
                        'amount' => floatval($p['amount'])
                    ];
                }

                foreach($sales as $sale) {
                    $saleDebt = $request->type == 'Credito' ? floatval($sale->debt) : floatval($sale->total);
                    if($saleDebt <= 0) continue;

                    $amountToPayForSale = 0;
                    $salePaymentsToRecord = [];

                    // Draw from funds
                    foreach($funds as &$fund) {
                        if($fund['amount'] <= 0) continue;
                        if($saleDebt <= 0) break;

                        $draw = min($fund['amount'], $saleDebt);
                        $fund['amount'] -= $draw;
                        $saleDebt -= $draw;
                        $amountToPayForSale += $draw;

                        $salePaymentsToRecord[] = [
                            'payment_method_id' => $fund['payment_method_id'],
                            'amount' => $draw
                        ];
                    }

                    if($amountToPayForSale > 0) {
                        $newDebt = $request->type == 'Credito' ? ($sale->debt - $amountToPayForSale) : 0;
                        $paid = $newDebt <= 0 ? 1 : 0;

                        $updateData = [
                            'debt' => $newDebt,
                            'paid' => $paid
                        ];
                        if ($photoPath) $updateData['photo'] = $photoPath;

                        $sale->update($updateData);

                        foreach($salePaymentsToRecord as $sp) {
                            Payment::create([
                                'sale_id' => $sale->id,
                                'payment_method_id' => $sp['payment_method_id'],
                                'amount' => $sp['amount'],
                                'date' => now()
                            ]);

                            CashboxMovement::create([
                                'cashbox_id' => $cashbox->id,
                                'sale_id' => $sale->id,
                                'user_id' => auth()->id(),
                                'payment_method_id' => $sp['payment_method_id'],
                                'type' => 'paid',
                                'amount' => $sp['amount'],
                                'date' => now()
                            ]);
                        }
                    }
                }
            });

            return response()->json([
                'status' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Error al registrar pago: " . $e->getMessage(), [
                'sale_id' => $request->sale_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id) {
        try {
            DB::transaction(function() use ($id) {
                $payment = Payment::findOrFail($id);
                $sale = $payment->sale;

                if (!$sale) {
                    throw new \Exception('Venta no encontrada.');
                }
                
                // Remove corresponding CashboxMovement
                CashboxMovement::where('sale_id', $sale->id)
                    ->where('payment_method_id', $payment->payment_method_id)
                    ->where('amount', $payment->amount)
                    ->where('type', 'paid')
                    ->orderBy('id', 'desc')
                    ->first()
                    ?->delete();

                // Restore sale debt
                $newDebt = $sale->debt + $payment->amount;
                // If it's not a credit, we should also calculate debt correctly or just make it unpaid
                $sale->update([
                    'debt' => $newDebt,
                    'paid' => 0
                ]);

                $payment->delete();
            });

            return response()->json([
                'status' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Error al restablecer pago: " . $e->getMessage(), [
                'payment_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Error al restablecer: ' . $e->getMessage()
            ]);
        }
    }

    public function excel(Request $request){
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = "ReportePagos_".now()->format('dm').".xlsx";
        return Excel::download(new PaymentsExport($request), $name);
    }

    public function pdf(Request $request){
        $payments = Payment::with(['sale.client', 'payment_method'])
        ->when($request->client_id, function($query, $client_id){
            return $query->whereHas('sale', function($query) use ($client_id){
                return $query->where('client_id', $client_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->when($request->type, function($query, $type){
            return $query->whereHas('sale', function($query) use ($type){
                return $query->where('type', $type);
            });
        })->latest('date')->get();

        $fpdf = new Fpdf;
        $fpdf->AddPage();
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }
        
        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $title = 'REPORTE DE MOVIMIENTOS';
        if($request->type == 'Credito') $title = 'REPORTE DE CRÉDITOS';
        if($request->type == 'Contado') $title = 'REPORTE DE CONTADO';
        $fpdf->Cell(190, 10, utf8_decode($title), 0, 1, 'C');
        
        $period = "Filtro: ";
        if($request->start_date) $period .= "Desde " . date('d/m/Y', strtotime($request->start_date)) . " ";
        if($request->end_date) $period .= "Hasta " . date('d/m/Y', strtotime($request->end_date));
        if(!$request->start_date && !$request->end_date) $period .= "Todos los registros";
        
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(190, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(10);

        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        
        $fpdf->Cell(70, 10, utf8_decode('CLIENTE'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('GUÍA/VENTA'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('MONTO'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('FORMA PAGO'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('FECHA'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);
        $total = 0;
        
        foreach($payments as $payment){
            $fpdf->Cell(70, 8, utf8_decode(optional(optional($payment->sale)->client)->name ?? 'Consumidor Final'), 1);
            $fpdf->Cell(30, 8, utf8_decode(optional($payment->sale)->guide ?? 'Manual'), 1, 0, 'C');
            $fpdf->Cell(30, 8, 'S/'.number_format($payment->amount, 2), 1, 0, 'R');
            $fpdf->Cell(30, 8, utf8_decode(optional($payment->payment_method)->name ?? 'N/A'), 1, 0, 'C');
            $fpdf->Cell(30, 8, $payment->date->format('d/m/Y'), 1, 1, 'C');
            $total += $payment->amount;
        }

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(100, 10, 'TOTAL RECAUDADO', 1, 0, 'R');
        $fpdf->Cell(30, 10, 'S/'.number_format($total, 2), 1, 0, 'R');
        $fpdf->Cell(60, 10, '', 1, 1);

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReportePagos_".now()->format('dm').".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('D', $name);
    }
}
