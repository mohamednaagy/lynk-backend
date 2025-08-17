<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement;

use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Tenancy;
use Throwable;

abstract class BaseCommoditiesSettlement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    public $tries = 10;

    public $backoff = 30;

    protected const CHUNK_SIZE = 10;

    public function __construct(protected int $localMarketOrderId)
    {
        // Disable tenancy inside the job
        app(Tenancy::class)->end();

        $className = class_basename(static::class);
        $queueName = 'local_market_commodities_settlement';
        self::logInfo("add $className job to queue $queueName", [
            'local_market_order_id' => $this->localMarketOrderId,
        ]);

        $this->onQueue($queueName);
    }

    protected function logInfo(string $message, array $data = []): void
    {
        $message = 'CommoditiesSettlement - '.$message;
        Log::channel('local_market')->info($message, $data);
    }

    protected function logError(string $message, array $data = []): void
    {
        $message = 'CommoditiesSettlement - '.$message;
        Log::channel('local_market')->error($message, $data);
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    /**
     * Unique ID for the job instance to prevent overlaps
     */
    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->localMarketOrderId;
    }

    public function failed(Throwable $e): void
    {
        $className = class_basename(static::class);

        Log::channel('local_market')->error("{$className} failed", [
            'error_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'order_id' => $this->localMarketOrderId,
        ]);
    }
}
