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
        'financing_order_id',
        'provider',
        'type',
        'reference',
        'status',
        'data',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'financing_order_id',
            'provider',
            'type',
            'reference',
            'status',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinancingOrder::class, 'financing_order_id', 'id');
    }

    public function traderHistories(): HasMany
    {
        return $this->hasMany(TraderHistory::class, 'trader_order_id', 'id');
    }
}
