<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'document',
        'name',
        'business_name',
        'address',
        'district',
        'email',
        'phone',
        'phone_2'
    ];

    public $timestamps = false;
}
