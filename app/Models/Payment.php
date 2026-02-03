<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'payment_method_id',
        'amount',
        'date'
    ];

    protected $dates = ['date'];
    
    public $timestamps = false;

    public function sale(){
        return $this->belongsTo(Sale::class);
    }

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class);
    }
}
