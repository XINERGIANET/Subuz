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
        'is_combo',
        'combo_products',
        'initial_stock',
        'stock_updated_at'
    ];

    protected $casts = [
        'combo_products' => 'array',
        'is_combo' => 'boolean'
    ];
    
    public function getCalculatedPriceAttribute()
    {
        if ($this->is_combo && is_array($this->combo_products)) {
            $total = 0;
            foreach ($this->combo_products as $cp) {
                $product = self::find($cp['id']);
                if ($product) {
                    $total += $product->price * ($cp['quantity'] ?? 1);
                }
            }
            return number_format($total, 2, '.', '');
        }
        return $this->price;
    }

    public $timestamps = false;
}
