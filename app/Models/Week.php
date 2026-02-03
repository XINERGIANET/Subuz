<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Week extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'year',
        'start_date',
        'end_date'
    ];

    protected $dates = ['start_date', 'end_date'];

    public $timestamps = false;

    public function sales(){
        return $this->hasMany(Sale::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }
}
