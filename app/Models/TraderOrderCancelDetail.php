<?php

namespace App\Models;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderOrderCancelDetail extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'creator_id',
        'cancel_reason',
        'cancel_step',
        'trader_order_id',
        'cancel_type',
    ];

    protected $casts = [
        'cancel_reason' => TraderOrderCancelReason::class,
    ];

    public function traderOrder()
    {
        return $this->belongsTo(TraderOrder::class);
    }

    public function shouldNotifyProvider(): bool
    {
        if ($this->cancelReason === TraderOrderCancelReason::FailureToPurchase ||
            $this->cancelReason === TraderOrderCancelReason::NoEligibleCommoditiesAvailable) {
            return false;
        }

        return true;
    }

    public function cancelMessage(): ?string
    {
        $reason = $this->cancel_reason;

        if (! $reason) {
            return '';
        }
        if ($reason->is(TraderOrderCancelReason::ExpiredContractSignTime)) {
            $timeLimit = $this->traderOrder->getRecentTimeLimit(
                TraderOrderTimeLimitType::ContractSignTimeLimit,
                TraderOrderTimeLimitStatus::Expired
            );

            return str_replace(':value', $timeLimit->default_value, $reason->description);
        }

        // Order cancelled by user: include creator name safely
        if ($reason->is(TraderOrderCancelReason::TraderOrderIsCancelled) ||
            $reason->is(TraderOrderCancelReason::FinancingOrderIsCancelled)) {
            $creator = $this->getCreator();

            return str_replace(':user', $creator['name'], $reason->description);
        }

        return $reason->description;
    }
}
