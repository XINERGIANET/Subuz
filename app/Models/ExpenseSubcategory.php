<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSubcategory extends Model
{
    use HasFactory;

    protected $fillable = ['expense_category_id', 'name', 'allowed_for_dispatchers'];

    protected $casts = [
        'allowed_for_dispatchers' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
