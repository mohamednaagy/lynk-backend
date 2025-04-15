<?php

namespace App\Jobs\TraderOrder\AutoCompleteSell;

use App\Actions\Contracts\Orders\TraderOrders\AutoCompleteSell;
use App\Jobs\TraderOrder\AutoCompleteSell\Exceptions\AutoCompleteSellFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessAutoCompleteSell implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private readonly int $traderOrderId, private readonly int $periodId)
    {
        Log::channel('bursam')->info('add ProcessAutoCompleteSell job to queue default');
    }

    public function handle(): void
    {
        try {
            app(AutoCompleteSell::class)->handle($this->traderOrderId, $this->periodId);

            Log::channel('bursam')->info('Auto complete sell performed', [
                'trader_order' => $this->traderOrderId,
                'period_id' => $this->periodId,
            ]);

        } catch (\Throwable $e) {
            Log::channel('bursam')->error('ProcessAutoCompleteSell job failed', [
                'trader_order' => $this->traderOrderId,
                'period_id' => $this->periodId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AutoCompleteSellFailed($e->getMessage(), $e->getCode());
        }
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}
