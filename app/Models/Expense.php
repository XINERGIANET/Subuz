<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'amount',
        'payment_method_id',
        'date'
    ];

    protected $dates = ['date'];
    
    public $timestamps = false;

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class);
    }
}
