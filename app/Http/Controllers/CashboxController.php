<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cashbox;
use App\Models\CashboxMovement;
use App\Models\Expense;

class CashboxController extends Controller
{
    public function index(){
        $cashbox = Cashbox::currentOpen();
        $movements = collect();
        $total_paid = 0;
        $total_debt = 0;
        $total_expenses = 0;
        $total_manual_income = 0;
        $suggested_closing_amount = null;
        $suggested_opening_amount = 0;

        if($cashbox){
            $movements = CashboxMovement::with(['sale.client', 'payment_method', 'user'])
                ->where('cashbox_id', $cashbox->id)
                ->get();

            // Combinamos con los gastos realizados durante la sesión de caja
            $session_expenses = Expense::with('payment_method')
                ->whereBetween('date', [$cashbox->opened_at, now()])
                ->get()
                ->map(function($e) {
                    return (object)[
                        'id' => null,
                        'date' => $e->date,
                        'type' => 'expense',
                        'amount' => $e->amount,
                        'note' => $e->description,
                        'payment_method_id' => $e->payment_method_id,
                        'payment_method' => $e->payment_method,
                        'sale' => null,
                        'user' => null
                    ];
                });

            $movements = $movements->concat($session_expenses)->sortByDesc('date');

            $total_paid = $movements->where('type', 'paid')->sum('amount');
            $total_debt = $movements->where('type', 'debt')->sum('amount');
            $total_manual_income = $movements->where('type', 'income')->sum('amount');
            $total_transfers = $movements->where('type', 'transfer')->sum('amount');
            
            $total_expenses = Expense::whereBetween('date', [$cashbox->opened_at, now()])
                ->sum('amount');

            // Para el monto sugerido de cierre (Efectivo), incluimos los transfers realizados en efectivo
            $cash_transfers = $movements->where('payment_method_id', 1)->where('type', 'transfer')->sum('amount');
            $suggested_closing_amount = ($cashbox->opening_amount + $movements->where('payment_method_id', 1)->where('type', 'paid')->sum('amount') + $movements->where('payment_method_id', 1)->where('type', 'income')->sum('amount') + $cash_transfers) - Expense::where('payment_method_id', 1)->whereBetween('date', [$cashbox->opened_at, now()])->sum('amount');
        } else {
            $last_box = Cashbox::where('is_open', 0)->latest('closed_at')->first();
            if($last_box){
                $suggested_opening_amount = $last_box->closing_amount;
            }
        }

        $payment_methods = \App\Models\PaymentMethod::all();
        $balances = [];

        foreach($payment_methods as $pm){
            $opening = 0;
            if($cashbox){
                if($pm->id == 1){
                    $opening = $cashbox->opening_amount;
                } else {
                    $opening = (is_array($cashbox->opening_balances) && isset($cashbox->opening_balances[$pm->id])) ? $cashbox->opening_balances[$pm->id] : 0;
                }
                
                $paid = $movements->where('payment_method_id', $pm->id)->where('type', 'paid')->sum('amount');
                $income = $movements->where('payment_method_id', $pm->id)->where('type', 'income')->sum('amount');
                $transfer = $movements->where('payment_method_id', $pm->id)->where('type', 'transfer')->sum('amount');
                
                // Calculamos los egresos específicos de este método de pago
                $expense = Expense::where('payment_method_id', $pm->id)
                    ->whereBetween('date', [$cashbox->opened_at, now()])
                    ->sum('amount');

                $balances[$pm->id] = $opening + $paid + $income + $transfer - $expense;
            } else {
                $balances[$pm->id] = 0;
            }
        }

        return view('cashbox.index', compact('cashbox', 'movements', 'total_paid', 'total_debt', 'total_expenses', 'total_manual_income', 'suggested_closing_amount', 'payment_methods', 'suggested_opening_amount', 'balances'));
    }

    public function storeIncome(Request $request){
        $request->validate([
            'amounts' => 'required|array',
            'amounts.*' => 'required|numeric|min:0',
            'payment_method_ids' => 'required|array',
            'payment_method_ids.*' => 'required|exists:payment_methods,id',
            'note' => 'required|string'
        ]);

        $cashbox = Cashbox::currentOpen();
        if(!$cashbox){
            return back()->with('error', 'No hay una caja abierta.');
        }

        foreach($request->amounts as $index => $amount){
            CashboxMovement::create([
                'cashbox_id' => $cashbox->id,
                'user_id' => auth()->id(),
                'payment_method_id' => $request->payment_method_ids[$index],
                'type' => 'income',
                'amount' => $amount,
                'note' => $request->note,
                'date' => now()
            ]);
        }

        return back()->with('message', 'Ingreso de caja registrado correctamente.');
    }

    public function storeTransfer(Request $request){
        $request->validate([
            'from_payment_method_id' => 'required|exists:payment_methods,id',
            'to_payment_method_id' => 'required|exists:payment_methods,id|different:from_payment_method_id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string'
        ]);

        $cashbox = Cashbox::currentOpen();
        if(!$cashbox){
            return back()->with('error', 'No hay una caja abierta.');
        }

        // Registrar la salida
        CashboxMovement::create([
            'cashbox_id' => $cashbox->id,
            'user_id' => auth()->id(),
            'payment_method_id' => $request->from_payment_method_id,
            'type' => 'transfer',
            'amount' => -$request->amount,
            'note' => 'Transferencia enviada: ' . $request->note,
            'date' => now()
        ]);

        // Registrar la entrada
        CashboxMovement::create([
            'cashbox_id' => $cashbox->id,
            'user_id' => auth()->id(),
            'payment_method_id' => $request->to_payment_method_id,
            'type' => 'transfer',
            'amount' => $request->amount,
            'note' => 'Transferencia recibida: ' . $request->note,
            'date' => now()
        ]);

        return back()->with('message', 'Transferencia entre cuentas registrada correctamente.');
    }

    public function open(Request $request){
        $request->validate([
            'opening_amount' => 'nullable|numeric|min:0',
            'opening_balances' => 'nullable|array'
        ]);

        if(Cashbox::currentOpen()){
            return back()->with('error', 'Ya hay una caja abierta.');
        }

        Cashbox::create([
            'opened_by' => auth()->id(),
            'opened_at' => now(),
            'opening_amount' => $request->opening_amount ? $request->opening_amount : 0,
            'opening_balances' => $request->opening_balances,
            'is_open' => 1
        ]);

        return back()->with('message', 'Caja aperturada');
    }

    public function close(Request $request){
        $request->validate([
            'closing_amount' => 'nullable|numeric',
            'note' => 'nullable|string'
        ]);

        $cashbox = Cashbox::currentOpen();

        if(!$cashbox){
            return back()->with('error', 'No hay una caja abierta.');
        }

        $closing_amount = $request->closing_amount;
        if($closing_amount === null || $closing_amount === ''){
            $total_paid = CashboxMovement::where('cashbox_id', $cashbox->id)
                ->where('type', 'paid')
                ->sum('amount');
            $total_manual_income = CashboxMovement::where('cashbox_id', $cashbox->id)
                ->where('type', 'income')
                ->sum('amount');
            $total_expenses = Expense::whereBetween('date', [$cashbox->opened_at, now()])
                ->sum('amount');
            $closing_amount = ($cashbox->opening_amount + $total_paid + $total_manual_income) - $total_expenses;
        }

        $payment_methods = \App\Models\PaymentMethod::all();
        $closing_balances = [];
        $movements = CashboxMovement::where('cashbox_id', $cashbox->id)->get();
        $total_expenses = Expense::whereBetween('date', [$cashbox->opened_at, now()])->sum('amount');

        foreach($payment_methods as $pm){
            $opening = ($pm->id == 1) ? $cashbox->opening_amount : ($cashbox->opening_balances[$pm->id] ?? 0);
            $paid = $movements->where('payment_method_id', $pm->id)->where('type', 'paid')->sum('amount');
            $income = $movements->where('payment_method_id', $pm->id)->where('type', 'income')->sum('amount');
            $transfer = $movements->where('payment_method_id', $pm->id)->where('type', 'transfer')->sum('amount');
            $expense = Expense::where('payment_method_id', $pm->id)
                ->whereBetween('date', [$cashbox->opened_at, now()])
                ->sum('amount');
            
            $closing_balances[$pm->id] = ($opening + $paid + $income + $transfer) - $expense;
        }

        $cashbox->update([
            'closed_by' => auth()->id(),
            'closed_at' => now(),
            'closing_amount' => $closing_balances[1], // Efectivo
            'closing_balances' => $closing_balances,
            'note' => $request->note,
            'is_open' => 0
        ]);
        return back()->with('message', 'Caja cerrada correctamente.');
    }

    public function destroyMovement(CashboxMovement $movement)
    {
        if(!auth()->user()->hasRole('admin')){
            abort(403);
        }

        $movement->delete();

        return back()->with('message', 'Movimiento de caja eliminado correctamente.');
    }
}
