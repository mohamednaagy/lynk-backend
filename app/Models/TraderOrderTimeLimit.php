<?php

namespace App\Models;

use App\Enums\TraderOrderTimeLimitType;
use App\Enums\TraderOrderTimeLimitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderOrderTimeLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'default_value',
        'effective_at',
        'trader_order_id',
        'status'
    ];

    protected $casts = [
        'type' => TraderOrderTimeLimitType::class,
        'status' => TraderOrderTimeLimitStatus::class,
        'effective_at' => 'datetime',
    ];

    /**
     * Get the TraderOrder that owns the TraderOrderTimeLimit
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function traderOrder()
    {
        return $this->belongsTo(TraderOrder::class);
    }

    /**
     * Mark the TraderOrderTimeLimit as expired.
     *
     * @return bool Whether the update was successful.
     */

    public function expire(): bool    {
        return $this->update(['status' => TraderOrderTimeLimitStatus::Expired]);
    }

    /**
     * Cancel the TraderOrderTimeLimit.
     *
     * Mark the TraderOrderTimeLimit as canceled. This will prevent the ExpireOrderJob from
     * running when the time limit is reached.
     *
     * @return bool Whether the update was successful.
     */
    public function cancel(): bool {
        return $this->update(['status' => TraderOrderTimeLimitStatus::Canceled]);
    }

    /**
     * Mark the TraderOrderTimeLimit as failed.
     *
     * This will update the status to 'Failed', indicating that the time limit
     * could not be met or processed as expected.
     *
     * @return bool Whether the update was successful.
     */

    public function fail(): bool{
        return $this->update(['status' => TraderOrderTimeLimitStatus::Failed]);
    }
}
