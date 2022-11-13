<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccRespondedToPtpOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $driver = config('trader.default');
        DB::transaction(function () use ($driver) {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            $lastTraderOrder = $financingOrder->traderOrders()->latest()->first();

            if (! $lastTraderOrder) {
                return;
            }

            $ptpDocument = Trader::driver($driver)->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'Promise to Purchase'
            );

            Trader::driver($driver)->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::GetPtpDocument
            );

            Trader::driver($driver)->attachDocumentToOrder(
                $lastTraderOrder,
                $ptpDocument,
                'promise_to_purchase',
                'base64'
            );

            Trader::driver($driver)->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::AttachPtpDocumentToOrder
            );

            $ttiDocument = Trader::driver($driver)->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'TTI - Holding certificate'
            );

            Trader::driver($driver)->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::GetTtiHoldingCertificateDocument
            );

            Trader::driver($driver)->attachDocumentToOrder(
                $lastTraderOrder,
                $ttiDocument,
                'tti_holding_certificate',
                'base64'
            );

            Trader::driver($driver)->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument
            );

            Trader::driver($driver)->updateOrderStatus($financingOrder, FinancingOrderStatus::PtpDocumentRetrieved);
        });
    }
}
