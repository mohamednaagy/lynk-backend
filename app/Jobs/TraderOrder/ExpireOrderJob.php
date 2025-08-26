<?php

namespace App\Jobs\TraderOrder;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderTimeLimitAction;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrder;
use App\Models\TraderOrderTimeLimit;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private TraderOrderTimeLimit $traderOrderTimeLimit;

    private string $jobUniqueId;

    private TraderOrder $traderOrder;

    public function __construct(int $traderOrderTimeLimitId)
    {
        $this->traderOrderTimeLimit = TraderOrderTimeLimit::findOrFail($traderOrderTimeLimitId);
        $this->jobUniqueId = 'expire_trader_order_'.$this->traderOrderTimeLimit->trader_order_id;
        $this->onQueue('expire_trader_order');
        $this->traderOrder = $this->traderOrderTimeLimit->traderOrder;
    }

    public function handle()
    {
        if (! $this->shouldExpire()) {
            $this->traderOrderTimeLimit->cancel();

            return;
        }

        try {
            match ($this->traderOrderTimeLimit->type->value) {
                TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit => $this->expireOrderDelivery(),
                TraderOrderTimeLimitType::ContractSignTimeLimit => $this->expireOrderContractSigned(),
                default => throw new \Exception("Unknown trader order time limit type: {$this->traderOrderTimeLimit->type->value}"),
            };
        } catch (\Exception $exception) {
            Log::channel(getSuitableLoggingFromTraderProvider($this->traderOrder))->error(formatLogTitle("ExpireOrderJob failed: {$exception->getMessage()}", $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'time_limit' => $this->traderOrderTimeLimit,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'exception' => $exception,
            ]);
            $this->traderOrderTimeLimit->fail();
        }
    }

    public function getJobUniqueId(): string
    {
        return $this->jobUniqueId;
    }

    /**
     * Determine if the TraderOrderTimeLimit should be expired.
     *
     * It will be expired if the TraderOrderTimeLimit is pending, the effective_at datetime is in the past,
     * and the related TraderOrder exists.
     */
    private function shouldExpire(): bool
    {
        return $this->traderOrderTimeLimit
            && $this->traderOrderTimeLimit->status->value === TraderOrderTimeLimitStatus::Pending
            && $this->traderOrderTimeLimit->action->value === TraderOrderTimeLimitAction::AutoCancelOrder
            && $this->traderOrderTimeLimit->effective_at <= now()
            && $this->traderOrder;
    }

    /**
     * Expire the order when it is delivery expirable, otherwise cancel the time limit.
     *
     * @return void
     */
    private function expireOrderDelivery()
    {
        if ($this->traderOrder->isDeliveryExpirable()) {
            Trader::driver($this->traderOrder->provider, $this->traderOrder->version)
                ->cancelTraderOrder($this->traderOrder, TraderOrderCancelReason::ExpiredConfirmationTimeLimit);
            $this->traderOrderTimeLimit->expire();
            Log::channel(getSuitableLoggingFromTraderProvider($this->traderOrder))->info(formatLogTitle("Expire order successfully", $this->traderOrder),[
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'reference' => $this->traderOrder->reference,
                'time_limit' => $this->traderOrderTimeLimit,
            ]);
            return;
        }
        $this->traderOrderTimeLimit->cancel();
        Log::channel(getSuitableLoggingFromTraderProvider($this->traderOrder))->info(formatLogTitle("Order is not expirable", $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'reference' => $this->traderOrder->reference,
            'time_limit' => $this->traderOrderTimeLimit,
        ]);
    }

    /**
     * Expires the order when it is contract sign expirable, otherwise cancels the time limit.
     *
     * @param void
     * @return void
     */
    private function expireOrderContractSigned()
    {
        $trader = Trader::driver($this->traderOrder->provider, $this->traderOrder->version);
        // Check if the order is contract sign limit expirable
        if ($trader->isContractSignLimitEligibleForExpiry($this->traderOrder)) {
            // Cancel the trader order with the expired contract sign time reason
            $trader->cancelTraderOrder($this->traderOrder, TraderOrderCancelReason::ExpiredContractSignTime);

            // Expire the trader order time limit
            $this->traderOrderTimeLimit->expire();

            // Log the successful expiration of the order
            Log::channel(getSuitableLoggingFromTraderProvider($this->traderOrder))->info(formatLogTitle("Expire order successfully", $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'reference' => $this->traderOrder->reference,
                'time_limit' => $this->traderOrderTimeLimit,
            ]);

            return;
        }

        // Cancel the trader order time limit if the order is not contract sign limit expirable
        $this->traderOrderTimeLimit->cancel();

        // Log the unsuccessful expiration of the order
        Log::channel(getSuitableLoggingFromTraderProvider($this->traderOrder))->info(formatLogTitle("Order is not expirable", $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'reference' => $this->traderOrder->reference,
            'time_limit' => $this->traderOrderTimeLimit,
        ]);
    }
}
