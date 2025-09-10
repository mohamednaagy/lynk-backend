<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
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

class ProcessBursamOtcCertificate implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamOtcCertificate: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {

        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamOtcCertificate Job - not found trader_order_id =>'.$this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);

                return;
            }

            if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamOtcCertificate job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'status' => $traderOrder->status->value,
                ]);

                return;
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CommoditySoldToMarket)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('ProcessBursamOtcCertificate: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'expected_action' => FinancingOrderHistory::CommoditySoldToMarket,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);

                return;
            }

            if ($traderOrder->doesLastActionMatchWith(FinancingOrderHistory::OnHold)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('ProcessBursamOtcCertificate: traderOrderId: '.$this->traderOrderId.' - Job skipped - on hold', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'expected_action' => FinancingOrderHistory::OnHold,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);

                return;
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CommoditySoldToMarket)) {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamOtcCertificate Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder?->order?->id,
                    'traderOrderId' => $this->traderOrderId,
                    'latest_action' => $traderOrder->traderHistories()->latest()->first()->action,
                    'expected_action' => FinancingOrderHistory::CommoditySoldToMarket,
                ]);

                return;
            }

            if ($traderOrder->doesLastActionMatchWith(FinancingOrderHistory::OnHold)) {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamOtcCertificate Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder?->order?->id,
                    'traderOrderId' => $this->traderOrderId,
                    'latest_action' => $traderOrder->traderHistories()->latest()->first()->action,
                    'expected_action' => FinancingOrderHistory::OnHold,
                ]);

                return;
            }

            Trader::driver('bursam', $traderOrder->version)->getOtcCertificateDetails($traderOrder);
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
        log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamOtcCertificate Job - trader_order_id => '.$this->traderOrderId, ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
    }
}
