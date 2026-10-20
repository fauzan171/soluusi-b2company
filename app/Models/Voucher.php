<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'nominal',
        'kuota',
        'usage',
        'start_date',
        'end_date',
        'is_active',
        'min_transaction',
        'max_discount',
        'discount_percentage'
    ];
}
