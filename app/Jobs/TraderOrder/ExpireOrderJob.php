<?php

namespace App\Jobs\TraderOrder;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderTimeLimitAction;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Jobs\FinancingOrders\NotifyLenderAboutExpireTraderOrder;
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

    /**
     * Centralized log channel name
     */
    private string $logChannel = LOG_CHANNEL_LOCAL_MARKET;

    public function __construct(int $traderOrderTimeLimitId)
    {
        $this->traderOrderTimeLimit = TraderOrderTimeLimit::findOrFail($traderOrderTimeLimitId);
        $this->jobUniqueId = 'expire_trader_order_'.$this->traderOrderTimeLimit->trader_order_id;
        $this->onQueue('expire_trader_order');
        $this->traderOrder = $this->traderOrderTimeLimit->traderOrder;
        // Log job construction (instantiation)
        Log::channel($this->logChannel)->info('ExpireOrderJob instantiated', [
            'time_limit_id' => $this->traderOrderTimeLimit->id,
            'order_id' => $this->traderOrder->id ?? null,
            'type' => $this->traderOrderTimeLimit->type->value,
            'status' => $this->traderOrderTimeLimit->status->value,
            'action' => $this->traderOrderTimeLimit->action->value,
            'effective_at' => $this->traderOrderTimeLimit->effective_at,
        ]);
    }

    public function handle()
    {
        Log::channel($this->logChannel)->info('ExpireOrderJob started', [
            'time_limit_id' => $this->traderOrderTimeLimit->id,
        ]);

        if (! $this->shouldExpire()) {
            $this->traderOrderTimeLimit->cancel();
            Log::channel($this->logChannel)->info('Time limit cancelled (not eligible to expire)', [
                'time_limit_id' => $this->traderOrderTimeLimit->id,
            ]);

            return;
        }

        try {
            match ($this->traderOrderTimeLimit->type->value) {
                TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit => $this->expireOrderDelivery(),
                TraderOrderTimeLimitType::ContractSignTimeLimit => $this->expireOrderContractSigned(),
                default => throw new \Exception("Unknown trader order time limit type: {$this->traderOrderTimeLimit->type->value}"),
            };
        } catch (\Exception $exception) {
            Log::channel($this->logChannel)->error(formatLogTitle("ExpireOrderJob failed: {$exception->getMessage()}", $this->traderOrder), [
                'time_limit_id' => $this->traderOrderTimeLimit->id,
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'exception' => $exception,
            ]);
            $this->traderOrderTimeLimit->fail();
        }
    }

    private function shouldExpire(): bool
    {
        Log::channel($this->logChannel)->info('should order Expire', [
            'time_limit_id' => $this->traderOrderTimeLimit->id,
            'traderOrderTimeLimit status' => $this->traderOrderTimeLimit->status->value,
            'traderOrderTimeLimit action' => $this->traderOrderTimeLimit->action->value,
            'traderOrderTimeLimit effective_at' => $this->traderOrderTimeLimit->effective_at,
            'now' => now(),
        ]);

        return $this->traderOrderTimeLimit
            && $this->traderOrderTimeLimit->status->value === TraderOrderTimeLimitStatus::Pending
            && $this->traderOrderTimeLimit->action->value === TraderOrderTimeLimitAction::AutoCancelOrder
            && $this->traderOrderTimeLimit->effective_at <= now()
            && $this->traderOrder;
    }

    private function expireOrderDelivery()
    {
        if ($this->traderOrder->isDeliveryExpirable()) {
            Trader::driver($this->traderOrder->provider, $this->traderOrder->version)
                ->cancelTraderOrder($this->traderOrder, TraderOrderCancelReason::ExpiredConfirmationTimeLimit);

            $this->traderOrderTimeLimit->expire();

            Log::channel($this->logChannel)->info(formatLogTitle('Expire order successfully', $this->traderOrder), [
                'time_limit_id' => $this->traderOrderTimeLimit->id,
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'reference' => $this->traderOrder->reference,
            ]);

            return;
        }

        $this->traderOrderTimeLimit->cancel();

        Log::channel($this->logChannel)->info(formatLogTitle('Order is not expirable', $this->traderOrder), [
            'time_limit_id' => $this->traderOrderTimeLimit->id,
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'reference' => $this->traderOrder->reference,
        ]);
    }

    private function expireOrderContractSigned()
    {
        $trader = Trader::driver($this->traderOrder->provider, $this->traderOrder->version);

        if ($trader->isContractSignLimitEligibleForExpiry($this->traderOrder)) {
            $trader->cancelTraderOrder($this->traderOrder, TraderOrderCancelReason::ExpiredContractSignTime);
            $this->traderOrderTimeLimit->expire();

            NotifyLenderAboutExpireTraderOrder::dispatch($this->traderOrder);

            Log::channel($this->logChannel)->info(formatLogTitle('Expire order successfully', $this->traderOrder), [
                'time_limit_id' => $this->traderOrderTimeLimit->id,
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'reference' => $this->traderOrder->reference,
            ]);

            return;
        }

        $this->traderOrderTimeLimit->cancel();

        Log::channel($this->logChannel)->info(formatLogTitle('Order is not expirable', $this->traderOrder), [
            'time_limit_id' => $this->traderOrderTimeLimit->id,
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'reference' => $this->traderOrder->reference,
        ]);
    }
}
