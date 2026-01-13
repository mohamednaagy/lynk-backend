<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBursamOrderResultNYY implements ShouldBeUnique, ShouldQueue
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
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamOrderResultNYY: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        $traderOrder = TraderOrder::query()
            ->find($this->traderOrderId);

        if (is_null($traderOrder)) {
            log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamOrderResultNYY Job - not found trader_order_id:'.$this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        if (! $traderOrder->canProcessBursamOrderResultNYY()) {
            return;
        }

        Trader::driver('bursam', $traderOrder->version)->fetchOrderResultNYY($traderOrder);
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
        log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamOrderResultNYY Job - trader_order_id => '.$this->traderOrderId, ['traderOrderId' => $this->traderOrderId,  'message' => $exception->getMessage(), 'line' => $exception->getLine(), 'file' => $exception->getFile(), 'trace' => $exception->getTraceAsString()]);

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if ($traderOrder) {
            $traderOrder->handleBursamOrderResultNYYFailure();
        }
    }
}
