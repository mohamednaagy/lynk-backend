<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Console\Commands\RunHoldTraderWhenMarketOpenCommand;
use App\Enums\BursamErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamOrderResultYNN implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        Log::channel(LOG_CHANNEL_BURSAM)->info('bursa purchasing step => ProcessBursamOrderResultYNN: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        log::channel(LOG_CHANNEL_BURSAM)->info('bursa purchasing step => Starting ProcessBursamOrderResultYNN Job - trader_order_id => '.$this->traderOrderId, ['traderOrderId' => $this->traderOrderId]);

        try {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->info('bursa purchasing step => Trader Order Is Null at ProcessBursamOrderResultYNN trader_order_id => '.$this->traderOrderId, ['traderOrderId' => $this->traderOrderId]);

                return;
            }

            if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamOrderResultYNN job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'status' => $traderOrder->status->value,
                ]);

                return;
            }
            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
                log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => Trader Order dosent have correct history at ProcessBursamOrderResultYNN', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                    'expected_action' => FinancingOrderHistory::GetTtiId,
                ]);

                return;
            }
            Trader::driver('bursam', $traderOrder->version)->fetchOrderResultYNN($traderOrder);
            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => Finishing ProcessBursamOrderResultYNN Job', $traderOrder), ['financingOrderId' => $traderOrder->financing_order_id, 'traderOrderId' => $this->traderOrderId]);

        } catch (Exception $e) {
            if ($this->shouldSkipRetry($e)) {
                $this->fail($e);

                return;
            }
            throw $e;
        }
    }

    private function shouldSkipRetry(Exception $e): bool
    {
        return $this->isUnavailableCommoditiesCode($e->getContext('failure_code'));
    }

    public function failed($exception)
    {
        log::channel(LOG_CHANNEL_BURSAM)->error('bursa purchasing step => failed ProcessBursamOrderResultYNN Job - trader_order_id => '.$this->traderOrderId, ['traderOrderId' => $this->traderOrderId,  'message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);

        DB::transaction(function () use ($exception) {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if ($traderOrder === null) {
                return;
            }
            (new RunHoldTraderWhenMarketOpenCommand)->handle();
            $traderOrder->order->update([
                'status' => $this->isUnavailableCommoditiesCode($exception->getContext('failure_code')) ? FinancingOrderStatus::PendingTraderOrder : FinancingOrderStatus::TradingFailure,
            ]);
            $cancel_reason = $this->isUnavailableCommoditiesCode($exception->getContext('failure_code')) ? TraderOrderCancelReason::NoEligibleCommoditiesAvailable : TraderOrderCancelReason::FailureToPurchase;
            app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, $cancel_reason);
            app(UpdateTraderOrderStatusToCancel::class)->handle(
                $traderOrder,
                $cancel_reason,
                method_exists($exception, 'getContext') ?
                    $exception->getContext('failure_reason')
                    : ''
            );
        });
    }

    private function isUnavailableCommoditiesCode(string $code): bool
    {
        return in_array($code, array_merge(
            BursamErrorCode::UNAVAILABLE_PRODUCT_ERROR_CODES, BursamErrorCode::UNAVAILABLE_INVENTORY_ERROR_CODES
        ));
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
