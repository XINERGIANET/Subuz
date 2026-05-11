<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankLoan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_name',
        'description',
        'total_amount',
        'installments_total',
        'monthly_amount',
        'start_date',
        'status',
        'currency'
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

    public function getNextPaymentDateAttribute()
    {
        if ($this->status == 'Pagado') return null;

        $startDate = \Carbon\Carbon::parse($this->start_date);
        $paidInstallments = $this->payments->pluck('installment_number')->unique()->toArray();

        for ($i = 1; $i <= $this->installments_total; $i++) {
            if (!in_array($i, $paidInstallments)) {
                return $startDate->copy()->addMonths($i - 1);
            }
        }

        return null;
    }
}
