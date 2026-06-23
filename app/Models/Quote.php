<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;
    protected $fillable = ['date', 'client_name', 'client_ruc', 'client_address', 'products', 'total', 'status'];
    protected $casts = [
        'products' => 'array',
    ];
}
