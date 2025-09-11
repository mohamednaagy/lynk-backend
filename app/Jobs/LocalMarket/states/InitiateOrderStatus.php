<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\PendingEligibleCommodities;
use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class InitiateOrderStatus extends BaseStatus
{
    protected function setUp(): void
    {
        $this->onQueue('local_market_order_initiation');
        $this->logQueueJob();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(PendingEligibleCommodities::class)->handle($this->localMarketOrder);
        $this->logQueueJob('success initiate local market order step');
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('local_market')->error("Error in InitiateOrderStatus order_id: $this->localMarketOrderID", [
            'order_id' => $this->localMarketOrderID,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->localMarketOrder->update([
            'status' => OrderStatus::FailedPurchase,
        ]);
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->localMarketOrder->id;
    }
}
