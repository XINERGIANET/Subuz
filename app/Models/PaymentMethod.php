<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'account_number',
        'holder_name',
        'show_in_reports',
        'show_in_liquidation_reports'
    ];

    public $timestamps = false;

    public function getCurrentBalanceAttribute()
    {
        $cashbox = \App\Models\Cashbox::currentOpen();

        if ($cashbox) {
            $opening = 0;
            if ($this->id == 1) {
                $opening = $cashbox->opening_amount;
            } else {
                $opening = (is_array($cashbox->opening_balances) && isset($cashbox->opening_balances[$this->id])) ? $cashbox->opening_balances[$this->id] : 0;
            }

            $movements = \App\Models\CashboxMovement::where('cashbox_id', $cashbox->id)
                ->where('payment_method_id', $this->id)
                ->get();

            $paid = $movements->where('type', 'paid')->sum('amount');
            $income = $movements->where('type', 'income')->sum('amount');
            $transfer = $movements->where('type', 'transfer')->sum('amount');

            $expense = \App\Models\Expense::where('payment_method_id', $this->id)
                ->whereBetween('date', [$cashbox->opened_at, now()])
                ->sum('amount');

            return $opening + $paid + $income + $transfer - $expense;
        } else {
            $last_box = \App\Models\Cashbox::where('is_open', 0)->latest('closed_at')->first();
            if ($last_box) {
                if ($this->id == 1) {
                    return $last_box->closing_amount;
                }
                return (is_array($last_box->closing_balances) && isset($last_box->closing_balances[$this->id])) ? $last_box->closing_balances[$this->id] : 0;
            }
        }
        return 0;
    }
}
