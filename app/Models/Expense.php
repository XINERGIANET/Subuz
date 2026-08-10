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
        'date',
        'real_date',
        'receipt_number',
        'operation_number',
        'expense_category_id',
        'expense_subcategory_id',
        'payment_method_id',
        'user_id'
    ];

    protected $dates = ['date'];
    
    public $timestamps = false;

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class)->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(ExpenseSubcategory::class, 'expense_subcategory_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
