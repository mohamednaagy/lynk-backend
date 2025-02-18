<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

abstract class BaseSellConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    protected const CHUNK_SIZE = 100;

    public function __construct()
    {
        $className = class_basename(static::class);
        self::logInfo("add $className job to queue local_market");
        $this->onQueue('local_market');
    }

    protected function logInfo(string $message, array $data = []): void
    {
        Log::channel('local_market')->info($message, $data);
    }

    protected function logError(string $message, array $data = []): void
    {
        Log::channel('local_market')->error($message, $data);
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
     * Unique ID for the job instance to prevent overlaps
     */
    public function uniqueId(): string
    {
        return __CLASS__;
    }
}
