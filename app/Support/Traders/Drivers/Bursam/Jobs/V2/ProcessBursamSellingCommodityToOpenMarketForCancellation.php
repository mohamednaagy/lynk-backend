<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamSellingCommodityToOpenMarketForCancellation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public $tries = 10;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        Log::channel('bursam')->info('start processing cancel trader order ProcessBursamSellingCommodityToOpenMarketForCancellation', ['traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::PendingCancellation)
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                return;
            }

            if ($traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity)) {
                Trader::driver('bursam', $traderOrder->version)
                    ->sellCommodityToBursam($traderOrder);
                Log::channel('bursam')->info('start processing cancel trader order finish sellCommodityToBursam', ['traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);
            }
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }

    public function failed($exception)
    {
        Log::channel('bursam')->error('ProcessBursamSellingCommodityToOpenMarketForCancellation', ['traderOrderId ' => $this->traderOrderId, 'trace' => $exception->getTraceAsString(), 'message' => $exception->getMessage()]);
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(5);
    }

    public function backoff(): array
    {
        return [60, 120, 120];
    }
}
