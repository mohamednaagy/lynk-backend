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
use Throwable;

abstract class BaseStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    protected const LOG_CHANNEL = 'local_market';

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
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle("failed {$this->className} local market order id {$this->localMarketOrderID}", $this->localMarketOrder), [
                'localMarketOrderId' => $this->localMarketOrderID,
                'order_reference' => $this->localMarketOrder->external_order_no,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function setUp(): void
    {
        $this->onQueue('local_market_states');
        $this->logQueueJob();
    }

    protected function logQueueJob(?string $message = null): void
    {
        $message = $message ?? "add {$this->className} job to queue local_market";

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle($message.' for the given order', $this->localMarketOrder),
            [
                'localMarketOrderId' => $this->localMarketOrder->id,
                'order_reference' => $this->localMarketOrder->external_order_no,
            ]);
    }

    public function failed(Throwable $exception): void
    {
        $errorMessage = formatLocalMarketOrderTitle("failed {$this->className}, the given ", $this->localMarketOrder);
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(
            $errorMessage,
            [
                'localMarketOrderId' => $this->localMarketOrderID,
                'order_reference' => $this->localMarketOrder->external_order_no,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }
}
