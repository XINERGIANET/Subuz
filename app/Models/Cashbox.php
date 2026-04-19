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
        'opening_balances',
        'closed_by',
        'closed_at',
        'closing_amount',
        'closing_balances',
        'is_open',
        'note'
    ];

    protected $dates = ['opened_at', 'closed_at'];

    protected $casts = [
        'opening_balances' => 'array',
        'closing_balances' => 'array'
    ];

    public $timestamps = false;

    public function movements(){
        return $this->hasMany(CashboxMovement::class);
    }

    public function openedBy(){
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(){
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function currentOpen(){
        return self::where('is_open', 1)->latest('opened_at')->first();
    }
}
