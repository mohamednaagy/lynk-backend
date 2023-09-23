<?php

namespace App\Models;

use App\Enums\WalletNotificationType;
use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WalletNotification extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'wallet_id',
        'type',
        'value',
    ];

    protected $casts = [
        'type' => WalletNotificationType::class,
        'value' => MoneyStringCast::class,
    ];
}
