<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Console\Commands\RunHoldTraderWhenMarketOpenCommand;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamBidCertificate implements ShouldBeUnique, ShouldQueue
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
        Log::channel(LOG_CHANNEL_BURSAM)->info('bursa purchasing step => ProcessBursamBidCertificate: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamBidCertificate Job - not found ', ['traderOrderId' => $this->traderOrderId]);
                return;
            }
            
            if($traderOrder->status->isNot(TraderOrderStatus::InProgress)){
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamBidCertificate job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId ,
                    'status' => $traderOrder->status->value]);
                return;
            }
                
            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiHoldingCertificateDocument)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => ProcessBursamBidCertificate: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId ,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                    'expected_action' => FinancingOrderHistory::GetTtiHoldingCertificateDocument,
                ]);
                return;
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiHoldingCertificateDocument)
            ) {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamBidCertificate Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder?->order?->id,
                    'traderOrderId' => $this->traderOrderId,
                    'latest_action' => $traderOrder->traderHistories()->latest()->first()->action  ,
                    'expected_action' => FinancingOrderHistory::GetTtiHoldingCertificateDocument,
                ]);
                return;
            }

            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa Purchasing Step => Starting ProcessBursamBidCertificate Job', $traderOrder), ['financingOrderId' => $traderOrder->financing_order_id, 'traderOrderId' => $this->traderOrderId]);

            Trader::driver('bursam', $traderOrder->version)
                ->getBidCertificateDetails($traderOrder);
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
        $traderOrder = TraderOrder::query()->find($this->traderOrderId);
        log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('bursa purchasing step => faild to get ProcessBursamBidCertificate and we will cancel order', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrderId,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        (new RunHoldTraderWhenMarketOpenCommand)->handle();

    }
}
