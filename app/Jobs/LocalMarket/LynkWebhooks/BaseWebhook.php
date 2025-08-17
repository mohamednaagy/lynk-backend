<?php

namespace App\Jobs\LocalMarket\LynkWebhooks;

use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

abstract class BaseWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    public function __construct(protected int $localMarketOrderId)
    {
        $this->onQueue('local_market_webhooks');
    }

    public function backoff(): array
    {
        return [60, 120, 180, 240, 300, 360, 420, 480, 540, 600];
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    /**
     * Unique identifier for job deduplication.
     */
    public function uniqueId(): string
    {
        return $this->localMarketOrderId
            ? __CLASS__.'_'.$this->localMarketOrderId
            : __CLASS__;
    }
}
