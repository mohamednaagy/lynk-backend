<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessBursamRunHoldTrader implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
        Log::channel('bursam')->info('ProcessBursamRunHoldTrader: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $holdTrader = TraderOrder::find($this->traderOrderId);
            Log::channel('bursam')->info('move Hold Trader Order ProcessBursamRunHoldTrader', ['financingOrderId' => $holdTrader->order->id,  'traderOrderId' => $holdTrader->id]);
            Trader::driver($holdTrader->provider, $holdTrader->version)->moveHoldTraderOrder($holdTrader);
        } catch (Exception $e) {
            Log::channel('bursam')->error('Failed to ProcessBursamRunHoldTrader', ['financingOrderId' => $holdTrader->order->id,  'traderOrderId' => $holdTrader->id, 'error' => $e->getMessage()]);
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
