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

        $movements = CashboxMovement::where('user_id', $dispatcher->id)
            ->whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['sale.client', 'payment_method'])
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
        $total_credit = 0;

        foreach($movements as $mov){
            if($mov->type == 'paid'){
                $method_name = optional($mov->payment_method)->name ?? 'Manual/Indeterminado';
                if(!isset($methods_totals[$method_name])) $methods_totals[$method_name] = 0;
                $methods_totals[$method_name] += $mov->amount;
                $total_delivered += $mov->amount;
            } elseif($mov->type == 'debt'){
                $total_credit += $mov->amount;
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
        $fpdf->Cell(30, 8, 'S/ '.number_format($total_credit, 2), 0, 1, 'R');
        
        $fpdf->SetFont('Montserrat', 'B', 11);
        $fpdf->Cell(60, 10, utf8_decode('TOTAL ENTREGADO:'), 0, 0, 'L');
        $fpdf->Cell(30, 10, 'S/ '.number_format($total_delivered, 2), 0, 1, 'R');
        $fpdf->Ln(5);

        // Detailed Table
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        
        $fpdf->Cell(25, 10, utf8_decode('GUÍA'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('FECHA'), 1, 0, 'C', true);
        $fpdf->Cell(60, 10, utf8_decode('CLIENTE'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('TIPO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('PAGO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('MONTO'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);
        
        foreach($movements as $mov){
            $sale = $mov->sale;
            if(!$sale) continue;
            $fpdf->Cell(25, 8, utf8_decode($sale->guide), 1, 0, 'C');
            $fpdf->Cell(25, 8, date('d/m/Y', strtotime($mov->date)), 1, 0, 'C');
            $fpdf->Cell(60, 8, utf8_decode(optional($sale->client)->name ?? 'Consumidor Final'), 1, 0, 'L');
            $fpdf->Cell(30, 8, utf8_decode($sale->type), 1, 0, 'C');
            $fpdf->Cell(25, 8, $mov->type == 'paid' ? 'PAGADO' : utf8_decode('CRÉDITO'), 1, 0, 'C');
            $fpdf->Cell(25, 8, 'S/ '.number_format($mov->amount, 2), 1, 1, 'R');
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

        $movements = CashboxMovement::where('user_id', $dispatcher->id)
            ->whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['sale.client', 'payment_method'])
            ->get();

        $methods_totals = [];
        $total_delivered = 0;
        $total_credit = 0;

        foreach ($movements as $mov) {
            if ($mov->type == 'paid') {
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
                if (!isset($methods_totals[$method_name])) $methods_totals[$method_name] = 0;
                $methods_totals[$method_name] += $mov->amount;
                $total_delivered += $mov->amount;
            } elseif ($mov->type == 'debt') {
                $total_credit += $mov->amount;
                $total_delivered += $mov->amount;
            }
        }

        $formatted_movements = $movements->map(function ($mov) {
            return [
                'guide' => optional($mov->sale)->guide ?? 'N/A',
                'date' => date('d/m/Y H:i', strtotime($mov->date)),
                'client' => optional(optional($mov->sale)->client)->name ?? 'Consumidor Final',
                'type' => optional($mov->sale)->type ?? 'N/A',
                'payment_status' => $mov->type == 'paid' ? 'PAGADO' : 'CRÉDITO',
                'amount' => number_format($mov->amount, 2, '.', '')
            ];
        });

        return response()->json([
            'status' => true,
            'dispatcher' => $dispatcher->name,
            'period' => [
                'start' => date('d/m/Y', strtotime($start_date)),
                'end' => date('d/m/Y', strtotime($end_date))
            ],
            'summary' => [
                'methods' => $methods_totals,
                'credit' => number_format($total_credit, 2, '.', ''),
                'total' => number_format($total_delivered, 2, '.', '')
            ],
            'movements' => $formatted_movements
        ]);
    }
}
