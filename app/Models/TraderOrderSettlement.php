<?php

namespace App\Models;

use App\Enums\TraderOrderSettlementStatus;
use App\Traits\HasCreator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraderOrderSettlement extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'trader_order_id',
        'is_commodities_settled',
        'status',
        'creator_id',
    ];

    protected $casts = [
        'is_commodities_settled' => 'boolean',
        'status' => TraderOrderSettlementStatus::class,
        'created_at' => 'datetime',
    ];

    public function traderOrder(): BelongsTo
    {
        return $this->belongsTo(TraderOrder::class, 'trader_order_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function isSettlementInProgress(): bool
    {
        return $this->is_commodities_settled === null
            && in_array($this->status->value, [TraderOrderSettlementStatus::Pending, TraderOrderSettlementStatus::InProgress]);
    }

    public function getCreatedAtAttribute(): string
    {
        return saudi_now('Y-m-d h:i:s A', Carbon::parse($this->attributes['created_at']));
    }
}
