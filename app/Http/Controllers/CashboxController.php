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
        $suggested_closing_amount = null;

        if($cashbox){
            $movements = CashboxMovement::with(['sale.client', 'payment_method', 'user'])
                ->where('cashbox_id', $cashbox->id)
                ->latest('date')
                ->get();

            $total_paid = $movements->where('type', 'paid')->sum('amount');
            $total_debt = $movements->where('type', 'debt')->sum('amount');
            $total_expenses = Expense::whereBetween('date', [$cashbox->opened_at, now()])
                ->sum('amount');
            $suggested_closing_amount = ($cashbox->opening_amount + $total_paid) - $total_expenses;
        }

        return view('cashbox.index', compact('cashbox', 'movements', 'total_paid', 'total_debt', 'total_expenses', 'suggested_closing_amount'));
    }

    public function open(Request $request){
        $request->validate([
            'opening_amount' => 'nullable|numeric|min:0'
        ]);

        if(Cashbox::currentOpen()){
            return back()->with('error', 'Ya hay una caja abierta.');
        }

        Cashbox::create([
            'opened_by' => auth()->id(),
            'opened_at' => now(),
            'opening_amount' => $request->opening_amount ? $request->opening_amount : 0,
            'is_open' => 1
        ]);

        return back()->with('message', 'Caja aperturada');
    }

    public function close(Request $request){
        $request->validate([
            'closing_amount' => 'nullable|numeric|min:0',
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
            $total_expenses = Expense::whereBetween('date', [$cashbox->opened_at, now()])
                ->sum('amount');
            $closing_amount = ($cashbox->opening_amount + $total_paid) - $total_expenses;
        }

        $cashbox->update([
            'closed_by' => auth()->id(),
            'closed_at' => now(),
            'closing_amount' => $closing_amount,
            'note' => $request->note,
            'is_open' => 0
        ]);

        Cashbox::create([
            'opened_by' => auth()->id(),
            'opened_at' => now(),
            'opening_amount' => $closing_amount,
            'is_open' => 1
        ]);

        return back()->with('message', 'Caja cerrada');
    }
}
