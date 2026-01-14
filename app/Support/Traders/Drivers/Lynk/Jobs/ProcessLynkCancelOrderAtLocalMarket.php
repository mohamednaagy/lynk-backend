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
use Illuminate\Support\Facades\Log;

class ProcessLynkCancelOrderAtLocalMarket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * The log channel to use for this job
     */
    private const LOG_CHANNEL = 'local_market';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->afterCommit = true; // Required: this job isn’t processed by TraderHistoryObserver.
        $this->onQueue('local_market_process');

        Log::channel(self::LOG_CHANNEL)->info('ProcessLynkCancelOrderAtLocalMarket job created', [
            'trader_order_id' => $this->traderOrderId,
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel(self::LOG_CHANNEL)->info('ProcessLynkCancelOrderAtLocalMarket job started , traderOrderId => '.$this->traderOrderId, [
            'trader_order_id' => $this->traderOrderId,
        ]);

        try {
            Log::channel(self::LOG_CHANNEL)->info('Starting database transaction for order cancellation , traderOrderId => '.$this->traderOrderId, [
                'trader_order_id' => $this->traderOrderId,
            ]);

            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::PendingCancellation)
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkCancelOrderAtLocalMarket not found trader_order_id =>'.$this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);

                return;
            }

            Log::channel(self::LOG_CHANNEL)->info(formatLogTitle('TraderOrder retrieved', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
            ]);

            if (is_null($traderOrder->cancelDetail)) {
                Log::channel(self::LOG_CHANNEL)->error(formatLogTitle('TraderOrder cancelDetail is null', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                ]);

                return;
            }

            // if condition to notify function to cancel detail from model (TODO:nagy)
            if ($traderOrder->cancelDetail->shouldNotifyProvider()) {
                Log::channel(self::LOG_CHANNEL)->info(formatLogTitle('Notifying provider to cancel order', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'should_notify_provider' => true,
                ]);

                LynkClient::of($traderOrder)->cancelOrder();

                Log::channel(self::LOG_CHANNEL)->info(formatLogTitle('Successfully notified LynkClient to cancel order', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                ]);
            } else {
                Log::channel(self::LOG_CHANNEL)->info(formatLogTitle('Unable to notify the LynkClient.', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'should_notify_provider' => false,
                ]);
            }

            Log::channel(self::LOG_CHANNEL)->info(formatLogTitle('Database transaction completed successfully', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
            ]);

            Log::channel(self::LOG_CHANNEL)->info('ProcessLynkCancelOrderAtLocalMarket job completed successfully traderOrderId => '.$this->traderOrderId, [
                'trader_order_id' => $this->traderOrderId,
            ]);

        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('error at ProcessLynkCancelOrderAtLocalMarket , cant add connect to local market to cancel order trader_order_id => '.$this->traderOrderId, [
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
