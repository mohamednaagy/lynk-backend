<?php

namespace App\Models;

use Bavix\Wallet\Models\Wallet as BaseWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends BaseWallet
{
    use HasFactory;

    public function getBalanceAttribute(): string
    {
        return $this->getRawOriginal('balance');
    }
}
