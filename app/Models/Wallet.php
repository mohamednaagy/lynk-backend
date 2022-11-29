<?php

namespace App\Models;

use App\Support\Wallets\Traits\CanPay;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;

/**
 * @property Money $balance
 */
class Wallet extends Model
{
    use HasFactory, CanPay;

    protected $fillable = [
        'holder_type',
        'holder_id',
        'name',
        'uuid',
        'currency',
    ];

    public function holder(): MorphTo
    {
        return $this->morphTo('holder');
    }

    public function getConnectionName()
    {
        return Config::get('wallet.database.connection', parent::getConnectionName());
    }

    public function getBalanceAttribute()
    {
        return new Money($this->transactions()->sum('amount'), $this->currency);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'wallet_id', 'id');
    }
}
