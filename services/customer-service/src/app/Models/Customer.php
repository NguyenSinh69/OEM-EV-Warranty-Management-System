<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // (Yêu cầu: id, name, email, phone)
    protected $fillable = [
        'name',
        'email',
        'phone',
    ];
    // === KẾT THÚC CODE TICKET #10 ===
}