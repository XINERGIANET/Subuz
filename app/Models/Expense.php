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
        'date',
        'expense_category_id',
        'expense_subcategory_id'
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
}
