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
        Log::channel('bursam')->info('bursa purchasing step => ProcessBursamOrderResultYNN: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        try {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if($traderOrder->status->isNot(TraderOrderStatus::InProgress) || $traderOrder->status->isNot(TraderOrderStatus::Initiated)){
                Log::channel('bursam')->warning('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status ( in progress or initaited ) in ProcessBursamOrderResultYNN job', ['traderOrderId' => $this->traderOrderId , 'status' => $traderOrder->status->value]);
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
                Log::channel('bursam')->warning('bursa purchasing step => ProcessBursamOrderResultYNN: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', [
                    'traderOrderId' => $this->traderOrderId ,
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'expected_action' => FinancingOrderHistory::GetTtiId,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);
                return;
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
                Log::channel('bursam')->warning('bursa purchasing step => ProcessBursamOrderResultYNN: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', [
                    'traderOrderId' => $this->traderOrderId ,
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'expected_action' => FinancingOrderHistory::GetTtiId,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);
                return;
            }
            Log::channel('bursam')->info('bursa purchasing step => Starting ProcessBursamOrderResultYNN Job', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId]);
            Trader::driver('bursam', $traderOrder->version)->fetchOrderResultYNN($traderOrder);
            Log::channel('bursam')->info('bursa purchasing step => Finishing ProcessBursamOrderResultYNN Job', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId]);

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
        Log::channel('bursam')->error('bursa purchasing step => failed ProcessBursamOrderResultYNN Job', ['traderOrderId' => $this->traderOrderId,  'message' => $exception->getMessage()]);

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
