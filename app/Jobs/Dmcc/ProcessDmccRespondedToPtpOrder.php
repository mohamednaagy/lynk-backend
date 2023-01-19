<?php

namespace App\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
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
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        // to unify
        DB::transaction(function () {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            $lastTraderOrder = $financingOrder->activeTraderOrder()
                ->whereIn('provider', ['dmcc', 'fake'])->first();

            if (! $lastTraderOrder) {
                return;
            }

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::PtpDocumentRetrieved)) {
                return;
            }

            $trader = Trader::driver($lastTraderOrder->provider);

            $ptpDocument = $trader->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'Promise to Purchase'
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::GetPtpDocument
            );

            $this->attachDocumentToOrder(
                $lastTraderOrder,
                $ptpDocument,
                FinancingOrderMediaCollection::PromiseToPurchase,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::AttachPtpDocumentToOrder
            );

            $ttiDocument = $trader->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'TTI - Holding certificate'
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::GetTtiHoldingCertificateDocument
            );

            $this->attachDocumentToOrder(
                $lastTraderOrder,
                $ttiDocument,
                FinancingOrderMediaCollection::TtiHoldingCertificate,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument
            );

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::PtpDocumentRetrieved);
        });
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('financingOrder'.$this->financingOrder)];
    }
}
