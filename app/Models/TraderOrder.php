<?php

namespace App\Models;

use App\Enums\TraderOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\VirtualColumn\VirtualColumn;

/**
 * @property mixed $reference
 */
class TraderOrder extends Model
{
    use HasFactory, VirtualColumn;

    protected $fillable = [];

    protected $guarded = [];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'financing_order_id',
            'provider',
            'status',
            'reference',
        ];
    }

    protected $casts = [
        'status' => TraderOrderStatus::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinancingOrder::class, 'financing_order_id', 'id');
    }

    public function traderHistories(): HasMany
    {
        return $this->hasMany(TraderHistory::class, 'trader_order_id', 'id');
    }
}
