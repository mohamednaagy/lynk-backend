<?php

namespace App\Models;

use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $casts = [
        'amount' => MoneyStringCast::class.':currency',
    ];
}
