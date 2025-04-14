<?php

namespace App\Jobs\TraderOrder\AutoCompleteSell;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderProceedCase;
use App\Jobs\TraderOrder\AutoCompleteSell\Exceptions\AutoCompleteSellFailed;
use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoCompleteSell implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $traderOrderId, private readonly int $periodId)
    {
        Log::info('add AutoCompleteSell job to queue default');
    }

    public function handle(): void
    {
        try {
            $traderOrder = TraderOrder::findOrFail($this->traderOrderId);

            app(MakeOrderProceed::class)->handle(
                $traderOrder,
                FinancingOrderProceedCase::ContractAndClientWakalaCompleted,
                true
            );

            $traderOrder->setAutoCompletePeriodId($this->periodId);

            Log::info('Auto complete sell performed', [
                'trader_order' => $this->traderOrderId,
                'period_id' => $this->periodId,
            ]);

        } catch (\Throwable $e) {
            Log::error('AutoCompleteSell job failed', [
                'trader_order' => $this->traderOrderId,
                'period_id' => $this->periodId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AutoCompleteSellFailed($e->getMessage(), $e->getCode());
        }

    }

    public function backoff(): array
    {
        return [60, 120, 180, 240, 300, 360, 420, 480, 540, 600];
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
