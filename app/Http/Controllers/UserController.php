<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CashboxMovement;
use App\Models\PaymentMethod;
use Codedge\Fpdf\Fpdf\Fpdf;

class UserController extends Controller
{
    public function indexDispatchers(){
        $dispatchers = User::where('role', 'despachador')->latest('id')->paginate(10);
        return view('users.dispatchers.index', compact('dispatchers'));
    }

    public function createDispatcher(){
        return view('users.dispatchers.create');
    }

    public function storeDispatcher(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user',
            'password' => 'required|string|min:5|confirmed'
        ]);

        User::create([
            'name' => $request->name,
            'user' => $request->user,
            'password' => Hash::make($request->password),
            'role' => 'despachador'
        ]);

        return redirect()->route('users.dispatchers.index')->with('message', 'Usuario despachador creado');
    }

    public function editDispatcher(User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }
        return view('users.dispatchers.edit', compact('dispatcher'));
    }

    public function updateDispatcher(Request $request, User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$dispatcher->id,
            'password' => 'nullable|string|min:5|confirmed'
        ]);

        $data = [
            'name' => $request->name,
            'user' => $request->user
        ];

        if($request->password){
            $data['password'] = Hash::make($request->password);
        }

        $dispatcher->update($data);

        return redirect()->route('users.dispatchers.index')->with('message', 'Usuario actualizado');
    }

    public function destroyDispatcher(User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }

        $dispatcher->delete();

        return redirect()->route('users.dispatchers.index')->with('message', 'Usuario eliminado');
    }

    // Assistants Management
    public function indexAssistants(){
        $assistants = User::where('role', 'asistente')->latest('id')->paginate(10);
        return view('users.assistants.index', compact('assistants'));
    }

    public function createAssistant(){
        return view('users.assistants.create');
    }

    public function storeAssistant(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user',
            'password' => 'required|string|min:5|confirmed'
        ]);

        User::create([
            'name' => $request->name,
            'user' => $request->user,
            'password' => Hash::make($request->password),
            'role' => 'asistente'
        ]);

        return redirect()->route('users.assistants.index')->with('message', 'Usuario asistente creado');
    }

    public function editAssistant(User $assistant){
        if($assistant->role !== 'asistente'){
            abort(404);
        }
        return view('users.assistants.edit', compact('assistant'));
    }

    public function updateAssistant(Request $request, User $assistant){
        if($assistant->role !== 'asistente'){
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$assistant->id,
            'password' => 'nullable|string|min:5|confirmed'
        ]);

        $data = [
            'name' => $request->name,
            'user' => $request->user
        ];

        if($request->password){
            $data['password'] = Hash::make($request->password);
        }

        $assistant->update($data);

        return redirect()->route('users.assistants.index')->with('message', 'Usuario actualizado');
    }

    public function destroyAssistant(User $assistant){
        if($assistant->role !== 'asistente'){
            abort(404);
        }

        $assistant->delete();

        return redirect()->route('users.assistants.index')->with('message', 'Usuario eliminado');
    }

    public function dispatcherReport(Request $request, User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }

        $start_date = $request->start_date ?? now()->toDateString();
        $end_date = $request->end_date ?? now()->toDateString();

        $movements = CashboxMovement::where(function($q) use ($dispatcher) {
                $q->where('user_id', $dispatcher->id)->whereNull('dispatcher_id')
                  ->orWhere('dispatcher_id', $dispatcher->id);
            })
            ->whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['sale.client', 'payment_method'])
            ->get();

        $expenses = \App\Models\Expense::where('user_id', $dispatcher->id)
            ->whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['category', 'subcategory', 'payment_method'])
            ->get();

        $fpdf = new Fpdf;
        $fpdf->AddPage();
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');
        
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }
        
        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('REPORTE DE ENTREGAS POR DESPACHADOR'), 0, 1, 'C');
        
        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->Cell(190, 8, utf8_decode('Despachador: ' . $dispatcher->name), 0, 1, 'C');
        
        $period = "Periodo: " . date('d/m/Y', strtotime($start_date)) . " al " . date('d/m/Y', strtotime($end_date));
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(190, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(5);

        // Payment Method Breakdown
        $methods_totals = [];
        $total_delivered = 0;
        $total_actual_credit = 0;
        $total_pending_cash = 0;

        foreach($movements as $mov){
            if($mov->type == 'paid'){
                $method_name = optional($mov->payment_method)->name ?? 'Manual/Indeterminado';
                if(!isset($methods_totals[$method_name])) $methods_totals[$method_name] = 0;
                $methods_totals[$method_name] += $mov->amount;
                $total_delivered += $mov->amount;
            } elseif($mov->type == 'debt'){
                if(optional($mov->sale)->type == 'Contado'){
                    $total_pending_cash += $mov->amount;
                } else {
                    $total_actual_credit += $mov->amount;
                }
                $total_delivered += $mov->amount;
            }
        }

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('RESUMEN DE COBRANZA'), 0, 1, 'L');
        
        $fpdf->SetFont('Montserrat', '', 11);
        $fpdf->SetTextColor(0, 0, 0);
        foreach($methods_totals as $name => $amount){
            $fpdf->Cell(60, 8, utf8_decode($name . ':'), 0, 0, 'L');
            $fpdf->Cell(30, 8, 'S/ '.number_format($amount, 2), 0, 1, 'R');
        }
        $fpdf->Cell(60, 8, utf8_decode('Ventas a Crédito:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ '.number_format($total_actual_credit, 2), 0, 1, 'R');
        $fpdf->Cell(60, 8, utf8_decode('Pendientes de Pago:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ '.number_format($total_pending_cash, 2), 0, 1, 'R');
        
        $fpdf->SetFont('Montserrat', 'B', 11);
        $fpdf->Cell(60, 10, utf8_decode('TOTAL ENTREGADO:'), 0, 0, 'L');
        $fpdf->Cell(30, 10, 'S/ '.number_format($total_delivered, 2), 0, 1, 'R');
        $fpdf->Ln(5);

        if($expenses->count() > 0) {
            $fpdf->SetFont('Montserrat', 'B', 12);
            $fpdf->SetTextColor(2, 93, 166);
            $fpdf->Cell(190, 10, utf8_decode('RESUMEN DE GASTOS'), 0, 1, 'L');
            
            $fpdf->SetFont('Montserrat', '', 11);
            $fpdf->SetTextColor(0, 0, 0);

            $expenses_totals = [];
            $total_expenses = 0;
            foreach($expenses as $exp) {
                $method_name = optional($exp->payment_method)->name ?? 'Manual';
                if(!isset($expenses_totals[$method_name])) $expenses_totals[$method_name] = 0;
                $expenses_totals[$method_name] += $exp->amount;
                $total_expenses += $exp->amount;
            }

            foreach($expenses_totals as $name => $amount){
                $fpdf->Cell(60, 8, utf8_decode($name . ':'), 0, 0, 'L');
                $fpdf->Cell(30, 8, 'S/ '.number_format($amount, 2), 0, 1, 'R');
            }

            $fpdf->SetFont('Montserrat', 'B', 11);
            $fpdf->Cell(60, 10, utf8_decode('TOTAL GASTOS:'), 0, 0, 'L');
            $fpdf->Cell(30, 10, 'S/ '.number_format($total_expenses, 2), 0, 1, 'R');
            $fpdf->Ln(5);
        } else {
            $expenses_totals = [];
        }

        $efectivo_cobranza = isset($methods_totals['Efectivo']) ? $methods_totals['Efectivo'] : (isset($methods_totals['Manual']) ? $methods_totals['Manual'] : 0);
        $efectivo_gastos = isset($expenses_totals['Efectivo']) ? $expenses_totals['Efectivo'] : (isset($expenses_totals['Manual']) ? $expenses_totals['Manual'] : 0);
        $efectivo_entregar = $efectivo_cobranza - $efectivo_gastos;

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 8, utf8_decode('RESUMEN DE EFECTIVO PARA ENTREGA'), 0, 1, 'L');
        $fpdf->SetFont('Montserrat', '', 11);
        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->Cell(60, 8, utf8_decode('Efectivo Cobranza:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ '.number_format($efectivo_cobranza, 2), 0, 1, 'R');
        $fpdf->Cell(60, 8, utf8_decode('Efectivo Gastos:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, '- S/ '.number_format($efectivo_gastos, 2), 0, 1, 'R');
        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(40, 150, 60);
        $fpdf->Cell(60, 10, utf8_decode('EFECTIVO A ENTREGAR:'), 0, 0, 'L');
        $fpdf->Cell(30, 10, 'S/ '.number_format($efectivo_entregar, 2), 0, 1, 'R');
        $fpdf->Ln(5);

        $current_movements = [];
        $prev_payments_data = [];

        foreach($movements as $mov){
            $sale = $mov->sale;
            if(!$sale) continue;
            
            if ($mov->type == 'paid' && $sale->date->toDateString() < $start_date) {
                $prev_payments_data[] = $mov;
            } else {
                $current_movements[] = $mov;
            }
        }

        if(count($prev_payments_data) > 0){
            $fpdf->SetFont('Montserrat', 'B', 12);
            $fpdf->SetTextColor(28, 115, 71);
            $fpdf->Cell(190, 10, utf8_decode('DETALLE DE PAGOS DE VENTAS ANTERIORES'), 0, 1, 'L');

            $fpdf->SetFillColor(230, 245, 235);
            $fpdf->SetTextColor(0, 0, 0);
            $fpdf->SetFont('Montserrat', 'B', 9);

            $fpdf->Cell(25, 8, utf8_decode('GUÍA'), 1, 0, 'C', true);
            $fpdf->Cell(25, 8, utf8_decode('F. VENTA'), 1, 0, 'C', true);
            $fpdf->Cell(80, 8, utf8_decode('CLIENTE'), 1, 0, 'C', true);
            $fpdf->Cell(35, 8, utf8_decode('MÉTODO'), 1, 0, 'C', true);
            $fpdf->Cell(25, 8, utf8_decode('MONTO'), 1, 1, 'C', true);

            $fpdf->SetFont('Montserrat', '', 8);
            foreach($prev_payments_data as $mov){
                $sale = $mov->sale;
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
                $fpdf->Cell(25, 7, utf8_decode($sale->guide), 1, 0, 'C');
                $fpdf->Cell(25, 7, $sale->date->format('d/m/Y'), 1, 0, 'C');
                $fpdf->Cell(80, 7, utf8_decode(substr(optional($sale->client)->name ?? 'Consumidor Final', 0, 40)), 1, 0, 'L');
                $fpdf->Cell(35, 7, utf8_decode($method_name), 1, 0, 'C');
                $fpdf->Cell(25, 7, 'S/ '.number_format($mov->amount, 2), 1, 1, 'R');
            }
            $fpdf->Ln(5);
        }

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('ENTREGAS DEL PERIODO'), 0, 1, 'L');

        // Detailed Table
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        
        $fpdf->Cell(25, 10, utf8_decode('GUÍA'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('FECHA'), 1, 0, 'C', true);
        $fpdf->Cell(60, 10, utf8_decode('CLIENTE'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('MÉTODO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('PAGO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('MONTO'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);

        foreach($current_movements as $mov){
            $sale = $mov->sale;
            $method_name = '';
            $payment_status = '';

            if($mov->type == 'paid'){
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
                $payment_status = 'PAGADO';
            } else {
                if($sale->type == 'Contado'){
                    $method_name = 'PENDIENTE';
                    $payment_status = 'CONTADO';
                } else {
                    $method_name = 'N/A';
                    $payment_status = utf8_decode('CRÉDITO');
                }
            }

            $fpdf->Cell(25, 8, utf8_decode($sale->guide), 1, 0, 'C');
            $fpdf->Cell(25, 8, date('d/m/Y', strtotime($mov->date)), 1, 0, 'C');
            $fpdf->Cell(60, 8, utf8_decode(substr(optional($sale->client)->name ?? 'Consumidor Final', 0, 30)), 1, 0, 'L');
            $fpdf->Cell(30, 8, utf8_decode($method_name), 1, 0, 'C');
            $fpdf->Cell(25, 8, $payment_status, 1, 0, 'C');
            $fpdf->Cell(25, 8, 'S/ '.number_format($mov->amount, 2), 1, 1, 'R');
        }

        if($expenses->count() > 0) {
            $fpdf->Ln(5);
            $fpdf->SetFont('Montserrat', 'B', 12);
            $fpdf->SetTextColor(200, 50, 50); // Red color for expenses
            $fpdf->Cell(190, 10, utf8_decode('GASTOS REGISTRADOS'), 0, 1, 'L');

            // Detailed Table
            $fpdf->SetFillColor(200, 50, 50);
            $fpdf->SetTextColor(255, 255, 255);
            $fpdf->SetFont('Montserrat', 'B', 9);
            
            $fpdf->Cell(25, 10, utf8_decode('FECHA'), 1, 0, 'C', true);
            $fpdf->Cell(60, 10, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C', true);
            $fpdf->Cell(50, 10, utf8_decode('CATEGORÍA'), 1, 0, 'C', true);
            $fpdf->Cell(30, 10, utf8_decode('MÉTODO'), 1, 0, 'C', true);
            $fpdf->Cell(25, 10, utf8_decode('MONTO'), 1, 1, 'C', true);

            $fpdf->SetTextColor(0, 0, 0);
            $fpdf->SetFont('Montserrat', '', 8);

            foreach($expenses as $exp){
                $fpdf->Cell(25, 8, date('d/m/Y', strtotime($exp->date)), 1, 0, 'C');
                $fpdf->Cell(60, 8, utf8_decode(substr($exp->description, 0, 35)), 1, 0, 'L');
                $fpdf->Cell(50, 8, utf8_decode(substr(optional($exp->category)->name, 0, 25)), 1, 0, 'L');
                $fpdf->Cell(30, 8, utf8_decode(optional($exp->payment_method)->name ?? 'N/A'), 1, 0, 'C');
                $fpdf->Cell(25, 8, 'S/ '.number_format($exp->amount, 2), 1, 1, 'R');
            }
        }

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "Reporte_" . str_replace(' ', '_', $dispatcher->name) . "_" . now()->format('dm') . ".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('D', $name);
    }

    public function dispatcherReportData(Request $request, User $dispatcher)
    {
        if ($dispatcher->role !== 'despachador') {
            return response()->json(['status' => false, 'error' => 'Usuario no es despachador'], 404);
        }

        $start_date = $request->start_date ?? now()->toDateString();
        $end_date = $request->end_date ?? now()->toDateString();

        $movements = CashboxMovement::where(function($q) use ($dispatcher) {
                $q->where('user_id', $dispatcher->id)->whereNull('dispatcher_id')
                  ->orWhere('dispatcher_id', $dispatcher->id);
            })
            ->whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['sale.client', 'payment_method'])
            ->get();

        $expenses = \App\Models\Expense::where('user_id', $dispatcher->id)
            ->whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['category', 'subcategory', 'payment_method'])
            ->get();

        $methods_totals = [];
        $total_delivered = 0;
        $total_actual_credit = 0;
        $total_pending_cash = 0;

        foreach ($movements as $mov) {
            if ($mov->type == 'paid') {
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
                if (!isset($methods_totals[$method_name])) $methods_totals[$method_name] = 0;
                $methods_totals[$method_name] += $mov->amount;
                $total_delivered += $mov->amount;
            } elseif ($mov->type == 'debt') {
                if(optional($mov->sale)->type == 'Contado'){
                    $total_pending_cash += $mov->amount;
                } else {
                    $total_actual_credit += $mov->amount;
                }
                $total_delivered += $mov->amount;
            }
        }

        $current_movements = [];
        $prev_payments_data = [];

        foreach ($movements as $mov) {
            $sale = $mov->sale;
            if(!$sale) continue;

            $method_name = 'N/A';
            $payment_status = $mov->type == 'paid' ? 'PAGADO' : 'CRÉDITO';

            if($mov->type == 'paid'){
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
            } else {
                if(optional($sale)->type == 'Contado'){
                    $method_name = 'PENDIENTE';
                    $payment_status = 'CONTADO';
                }
            }

            $formatted_mov = [
                'guide' => optional($sale)->guide ?? 'N/A',
                'date' => date('d/m/Y H:i', strtotime($mov->date)),
                'client' => optional(optional($sale)->client)->name ?? 'Consumidor Final',
                'type' => $method_name,
                'payment_status' => $payment_status,
                'amount' => number_format($mov->amount, 2, '.', '')
            ];

            if ($mov->type == 'paid' && $sale->date->toDateString() < $start_date) {
                $prev_payments_data[] = $formatted_mov;
            } else {
                $current_movements[] = $formatted_mov;
            }
        }

        $formatted_expenses = [];
        $expenses_totals = [];
        $total_expenses_amount = 0;

        foreach($expenses as $exp) {
            $method_name = optional($exp->payment_method)->name ?? 'Manual';
            if(!isset($expenses_totals[$method_name])) $expenses_totals[$method_name] = 0;
            $expenses_totals[$method_name] += $exp->amount;
            $total_expenses_amount += $exp->amount;

            $formatted_expenses[] = [
                'date' => date('d/m/Y H:i', strtotime($exp->date)),
                'description' => $exp->description,
                'category' => optional($exp->category)->name ?? 'N/A',
                'payment_method' => $method_name,
                'amount' => number_format($exp->amount, 2, '.', '')
            ];
        }

        $efectivo_cobranza = $methods_totals['Efectivo'] ?? ($methods_totals['Manual'] ?? 0);
        $efectivo_gastos = $expenses_totals['Efectivo'] ?? ($expenses_totals['Manual'] ?? 0);
        $efectivo_entregar = $efectivo_cobranza - $efectivo_gastos;

        return response()->json([
            'status' => true,
            'dispatcher' => $dispatcher->name,
            'period' => [
                'start' => date('d/m/Y', strtotime($start_date)),
                'end' => date('d/m/Y', strtotime($end_date))
            ],
            'summary' => [
                'methods' => $methods_totals,
                'credit' => number_format($total_actual_credit, 2, '.', ''),
                'pending' => number_format($total_pending_cash, 2, '.', ''),
                'total' => number_format($total_delivered, 2, '.', ''),
                'expenses_methods' => $expenses_totals,
                'expenses_total' => number_format($total_expenses_amount, 2, '.', ''),
                'cash_collected' => number_format($efectivo_cobranza, 2, '.', ''),
                'cash_expenses' => number_format($efectivo_gastos, 2, '.', ''),
                'cash_handover' => number_format($efectivo_entregar, 2, '.', '')
            ],
            'movements' => $current_movements,
            'previous_payments' => $prev_payments_data,
            'previous_payments_count' => count($prev_payments_data),
            'expenses' => $formatted_expenses
        ]);
    }
}
