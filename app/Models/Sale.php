<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
        'date',
        'week_id',
        'guide',
        'type',
        'payment_method_id',
        'client_id',
        'total',
        'debt',
        'paid'
    ];

    protected $dates = ['date'];
    
    public $timestamps = false;

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class);
    }

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function week(){
        return $this->belongsTo(Week::class);
    }

    public function details(){
        return $this->hasMany(SaleDetail::class);
    }
}
