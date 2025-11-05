<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vin',
        'model',
        'year',
        'color',
        'purchase_date',
        'warranty_start_date',
        'warranty_end_date',
        'status'
    ];

    // Định dạng ngày tháng
    protected $casts = [
        'purchase_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
    ];
    
}