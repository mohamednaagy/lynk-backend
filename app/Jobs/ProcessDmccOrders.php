<?php

namespace App\Jobs;

use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        FinancingOrder::query()
            ->where('status', '!=', FinancingOrderStatus::Completed)
            ->chunk(10, function ($ordersCollection) {
                $ordersCollection->each(function ($order) {
                    match ($order->status) {
                        FinancingOrderStatus::InProgress => $this->handleInProgressOrder($order),
                        FinancingOrderStatus::ClientWakalaCompleted => $this->handleClientWakalaCompletedOrder($order),
                        FinancingOrderStatus::WaitingPurchasingCommodity => $this->handleWaitingPurchasingCommodityOrder($order),
                        FinancingOrderStatus::RespondedToPtp => $this->handleRespondedToPtpOrder($order),
                        FinancingOrderStatus::PtpDocumentRetrieved => $this->handlePtpDocumentRetrievedOrder($order),
                        FinancingOrderStatus::ContractSigned => $this->handleContractSignedOrder($order),
                        FinancingOrderStatus::CommoditySoldToCustomer => $this->handleCommoditySoldToCustomerOrder($order),
                        FinancingOrderStatus::MurabhaOfferIssued => $this->handleMurabhaOfferIssuedOrder($order),
                    };
                });
            });
    }

    private function handleInProgressOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            Sms::send('hello', '01011311797');
            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::WaitingClientWakala);
        });
    }

    private function handleClientWakalaCompletedOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            if ($financingOrder->traderOrders()->whereIn('status', [
                TraderOrderStatus::InProgress,
                TraderOrderStatus::Completed,
            ])->count() > 0) {
                return;
            }

            Trader::driver('dmcc')->getTtiId($financingOrder);

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::WaitingPurchasingCommodity);
        });
    }

    private function handleWaitingPurchasingCommodityOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            $lastTraderOrder = $financingOrder->traderOrders->last();

            Trader::driver('dmcc')->respondPtpService($lastTraderOrder->reference);

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::RespondedToPtp);
        });
    }

    private function handleRespondedToPtpOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            $lastTraderOrder = $financingOrder->traderOrders->last();

            $ptpDocument = Trader::driver('dmcc')->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'Promise to Purchase'
            );

            Trader::driver('dmcc')->attachDocumentToOrder(
                $lastTraderOrder,
                $ptpDocument,
                'promise_to_purchase',
                'base64'
            );

            $ttiDocument = Trader::driver('dmcc')->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'TTI - Holding certificate'
            );

            Trader::driver('dmcc')->attachDocumentToOrder(
                $lastTraderOrder,
                $ttiDocument,
                'tti_holding_certificate',
                'base64'
            );

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::PtpDocumentRetrieved);
        });
    }

    private function handlePtpDocumentRetrievedOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            $lastTraderOrder = $financingOrder->traderOrders->last();

            Trader::driver('dmcc')->createTransferOwnershipToLenderDocument(
                $lastTraderOrder,
                $lastTraderOrder->reference
            );

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::CommodityPurchased);
        });
    }

    private function handleContractSignedOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            $lastTraderOrder = $financingOrder->traderOrders->last();

            Trader::driver('dmcc')->createSellingCommodityToCustomerDocument(
                $lastTraderOrder,
                $lastTraderOrder->reference
            );

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::CommoditySoldToCustomer);
        });
    }

    private function handleCommoditySoldToCustomerOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            $lastTraderOrder = $financingOrder->traderOrders->last();

            $versionNo = Trader::driver('dmcc')->uploadTTIDocumentAndGetVersionNumber(
                $lastTraderOrder->reference
            );

            Trader::driver('dmcc')->issueMurabahaPurchaseOffer(
                $lastTraderOrder->reference,
                $versionNo
            );

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::MurabhaOfferIssued);
        });
    }

    private function handleMurabhaOfferIssuedOrder(FinancingOrder $financingOrder): void
    {
        DB::transaction(function () use ($financingOrder) {
            $lastTraderOrder = $financingOrder->traderOrders->last();

            $mpoDocument = Trader::driver('dmcc')->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'Murabaha Purchase Offer Document'
            );

            Trader::driver('dmcc')->attachDocumentToOrder(
                $lastTraderOrder,
                $mpoDocument,
                'murabha_purchase_order',
                'base64'
            );

            $warrantDocument = Trader::driver('dmcc')->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'Warrant Amendment Except Warrant No'
            );

            Trader::driver('dmcc')->attachDocumentToOrder(
                $lastTraderOrder,
                $warrantDocument,
                'warrant_amendment_except_warrant_no',
                'base64'
            );

            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::MurabahaSaleCompleted);
        });
    }

    private function updateOrderStatus(FinancingOrder $financingOrder, int $financingOrderStatus): void
    {
        $financingOrder->update([
            'status' => $financingOrderStatus,
        ]);
    }
}
