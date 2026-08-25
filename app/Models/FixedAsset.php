<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'allowed_for_dispatchers',
        'internal_code',
        'serial_number',
        'status',
        'purchase_date',
        'purchase_cost',
        'payment_method_id',
        'voucher_number',
        'current_client_id',
        'notes',
    ];

    protected $casts = [
        'allowed_for_dispatchers' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'current_client_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function assignments()
    {
        return $this->hasMany(FixedAssetAssignment::class);
    }

    public function getHistoryExpensesAttribute()
    {
        return \App\Models\Expense::where(function($query) {
                $query->where('description', 'like', '%(' . $this->name . ')%')
                      ->orWhere('description', 'like', '%Activo Fijo: ' . $this->name . '%');
            })
            ->orderBy('real_date', 'desc')
            ->get();
    }

    public function getHistoryIncomesAttribute()
    {
        return \App\Models\CashboxMovement::where('note', 'like', '%Cobro de Alquiler: ' . $this->name . '%')
            ->where('type', 'income')
            ->orderBy('date', 'desc')
            ->get();
    }
}
