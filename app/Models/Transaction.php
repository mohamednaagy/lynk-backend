<?php

namespace App\Models;

use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

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

    public function getConnectionName()
    {
        return Config::get('wallet.database.connection', parent::getConnectionName());
    }
}
