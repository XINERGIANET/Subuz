<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashboxMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashbox_id',
        'sale_id',
        'user_id',
        'payment_method_id',
        'type',
        'amount',
        'date',
        'note'
    ];

    protected $dates = ['date'];

    public $timestamps = false;

    public function cashbox(){
        return $this->belongsTo(Cashbox::class);
    }

    public function sale(){
        return $this->belongsTo(Sale::class);
    }

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
