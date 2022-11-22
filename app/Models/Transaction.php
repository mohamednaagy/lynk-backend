<?php

namespace App\Models;

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
    ];
}
