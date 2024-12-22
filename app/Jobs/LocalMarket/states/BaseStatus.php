<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

abstract class BaseStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    protected LocalMarketWebhook $localMarketWebhook;

    protected LocalMarketOrder $localMarketOrder;

    private string $className;

    public function __construct(protected int $localMarketOrderID)
    {
        try {
            $this->localMarketOrder = LocalMarketOrder::findOrFail($this->localMarketOrderID);
            $this->className = class_basename(static::class);
            $this->localMarketWebhook = app(LocalMarketWebhook::class);

            $this->setUp();
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed {$this->className} market order id {$this->localMarketOrderID}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function setUp(): void
    {
        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    protected function logQueueJob(?string $message = null): void
    {
        $message = $message ?? "add {$this->className} job to queue local_market";

        Log::channel('local_market')->info($message.' for the given order',
            ['order_id' => $this->localMarketOrder->id]);
    }

    public function failed(\Exception $exception): void
    {
        $errorMessage = "failed {$this->className}, the given order: ".$this->localMarketOrderID;
        Log::channel('local_market')->error($errorMessage,
            ['message' => $exception->getMessage()]);
    }
}
