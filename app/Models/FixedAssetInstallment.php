<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_asset_assignment_id',
        'installment_number',
        'due_date',
        'amount',
        'status',
        'paid_date',
        'cashbox_movement_id'
    ];

    public function assignment()
    {
        return $this->belongsTo(FixedAssetAssignment::class, 'fixed_asset_assignment_id');
    }

    public function cashboxMovement()
    {
        return $this->belongsTo(CashboxMovement::class);
    }
}
