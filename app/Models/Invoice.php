<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'client_id',
        'date',
        'total',
        'status',
        'notes'
    ];

    protected $dates = ['date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'invoice_sale');
    }
}
