<?php

namespace App\Models;

use App\Enums\WalletNotificationType;
use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WalletNotification extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'wallet_id',
        'type',
        'value',
        'notified_at',
    ];

    protected $casts = [
        'type' => WalletNotificationType::class,
        'value' => MoneyStringCast::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function markAsNotified(): bool
    {
        return $this->fill([
            'notified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function markAsNotNotified(): bool
    {
        return $this->fill([
            'notified_at' => null,
        ])->save();
    }

    public function isNotified(): bool
    {
        return ! is_null($this->notified_at);
    }
}
