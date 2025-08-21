<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Clients\LynkClient;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessLynkCancelOrderAtLocalMarket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::transaction(function () {
                $traderOrder = TraderOrder::query()
                    ->where('status', TraderOrderStatus::PendingCancellation)
                    ->lockForUpdate()
                    ->find($this->traderOrderId);

                if (is_null($traderOrder)) {
                    log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkCancelOrderAtLocalMarket not found trader_order_id =>' . $this->traderOrderId, [
                        'traderOrderId' => $this->traderOrderId,
                    ]);
                    return;
                }

                //if condition to notify function to cancel detail from model (TODO:nagy)
                if ($traderOrder->cancelDetail->shouldNotifyProvider()) {
                    log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('ProcessLynkCancelOrderAtLocalMarket: cancelling order to local market ', $traderOrder), [
                        'financingOrderId' => $traderOrder->financing_order_id, 
                        'traderOrderId' => $traderOrder->id,
                        'shouldNotifyProvider' => $traderOrder->cancelDetail->shouldNotifyProvider(),
                        'message' => 'cancelling order to local market',
                    ]);
                    LynkClient::of($traderOrder)->cancelOrder();
                }
            });
        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('error at ProcessLynkCancelOrderAtLocalMarket , cant add connect to local market to cancel order trader_order_id => '. $this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
