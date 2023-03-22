<?php

namespace App\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccRespondedToPtpOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    protected mixed $traderOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($traderOrder)
    {
        $this->traderOrder = $traderOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        // to unify
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);

            if ($traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachTtiHoldingCertificateDocument)) {
                return;
            }

            $trader = Trader::driver($traderOrder->provider);

            $ptpDocument = $trader->getDocumentByTypeAndTransaction(
                $traderOrder->reference,
                'Promise to Purchase'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetPtpDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $ptpDocument,
                TraderOrderMediaCollection::PromiseToPurchase,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachPtpDocumentToOrder
            );

            $ttiDocument = $trader->getDocumentByTypeAndTransaction(
                $traderOrder->reference,
                'TTI - Holding certificate'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetTtiHoldingCertificateDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $ttiDocument,
                TraderOrderMediaCollection::TtiHoldingCertificate,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument
            );
        });
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('traderOrder'.$this->traderOrder)];
    }
}
