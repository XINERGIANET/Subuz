<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'opened_by',
        'opened_at',
        'opening_amount',
        'closed_by',
        'closed_at',
        'closing_amount',
        'is_open',
        'note'
    ];

    protected $dates = ['opened_at', 'closed_at'];

    public $timestamps = false;

    public function movements(){
        return $this->hasMany(CashboxMovement::class);
    }

    public static function currentOpen(){
        return self::where('is_open', 1)->latest('opened_at')->first();
    }
}
