<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liquidation extends Model
{
    protected $fillable = [
        'client_id',
        'start_date',
        'end_date',
        'payment_date',
        'correlative_type',
        'general_correlative',
        'sale_correlatives',
        'total'
    ];

    protected $casts = [
        'sale_correlatives' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
