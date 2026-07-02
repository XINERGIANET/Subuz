<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'stock',
        'unit',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplies')
            ->withPivot('quantity');
    }
}
