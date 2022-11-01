<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\VirtualColumn\VirtualColumn;

class TraderOrder extends Model
{
    use HasFactory, VirtualColumn;

    protected $fillable = [
        'id',
        'order_id',
        'provider',
        'type',
        'reference',
        'data',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'order_id',
            'provider',
            'type',
            'reference',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinancingOrder::class, 'id', 'order_id');
    }

    public function traderHistory(): HasMany
    {
        return $this->hasMany(TraderHistory::class, 'trader_order_id', 'id');
    }
}
