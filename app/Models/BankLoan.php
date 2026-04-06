<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'description',
        'total_amount',
        'installments_total',
        'monthly_amount',
        'start_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getRemainingBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}
