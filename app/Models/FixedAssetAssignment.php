<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_asset_id',
        'client_id',
        'assigned_date',
        'returned_date',
        'assignment_type',
        'amount',
        'payment_frequency',
        'rental_mode',
        'total_installments',
        'notes',
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function installments()
    {
        return $this->hasMany(FixedAssetInstallment::class);
    }
}
