<?php

namespace App\Models;

use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Transfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => MoneyStringCast::class.':currency',
        'meta' => 'array',
    ];

    public function getConnectionName()
    {
        return Config::get('wallet.database.connection', parent::getConnectionName());
    }
}
