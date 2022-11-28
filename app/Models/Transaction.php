<?php

namespace App\Models;

use Cknow\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'uuid',
        'wallet_id',
        'reference_number',
        'amount',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => MoneyStringCast::class.':currency',
    ];
}
