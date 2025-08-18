<?php

namespace App\Jobs;

use App\Actions\Contracts\Orders\TraderOrders\Fees\FeeActionInterface;
use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job to apply order fees for a trader order.
 */
class ApplyOrderFeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected TraderOrder $traderOrder,
        protected FeeActionInterface $feeAction
    ) {
        $this->onQueue('apply_order_fees');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ApplyOrderFeesJob started', [
            'trader_order_id' => $this->traderOrder->id,
            'fee_action_class' => get_class($this->feeAction),
        ]);

        try {
            $this->feeAction->handle($this->traderOrder);
        } catch (Throwable $e) {
            Log::error('ApplyOrderFeesJob failed', [
                'trader_order_id' => $this->traderOrder->id,
                'fee_action_class' => get_class($this->feeAction),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ApplyOrderFeesJob failed permanently', [
            'trader_order_id' => $this->traderOrder->id,
            'fee_action_class' => get_class($this->feeAction),
            'error_message' => $exception->getMessage(),
        ]);
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrder->order->company_id;
    }

    public function middleware()
    {
        return [
            new WithoutOverlapping($this->uniqueId()),
        ];
    }
}
