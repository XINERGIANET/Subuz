<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document',
        'name',
        'business_name',
        'address',
        'district',
        'email',
        'phone',
        'phone_2',
        'type'
    ];

    public $timestamps = false;

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
