<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'stock',
        'reduces_stock',
        'is_loanable',
        'initial_stock',
        'stock_updated_at'
    ];

    public $timestamps = false;
}
