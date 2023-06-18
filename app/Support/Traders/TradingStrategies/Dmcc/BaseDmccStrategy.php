<?php

namespace App\Support\Traders\TradingStrategies\Dmcc;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Http\Request;

abstract class BaseDmccStrategy implements TraderStrategyInterface
{
    use TraderHelperTrait;

    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(DmccMurabhaStep::TraderOrderCreated);

        app(UpdateTraderOrder::class)->handle($traderOrder, $request->validated());

        $this->createStepHistories(
            $request,
            $traderOrder,
            DmccMurabhaStep::PurchasingCommodity
        );

        $this->transferOwnershipToLender($traderOrder, $request);

        ProcessAskClientForWakala::dispatch($traderOrder->id);
    }

    protected function transferOwnershipToLender(TraderOrder $traderOrder, $request)
    {
        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        // this (if) is a special case doesn't exist in history map
        if ($request->auto_generate_financing_institution_certificate) {
            $trader->createTransferOwnershipToLenderDocument($traderOrder);
        } elseif ($request->has('financing_institution_certificate')) {
            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($request->file('financing_institution_certificate'))),
                TraderOrderMediaCollection::TransferOwnershipToLender,
                'base64'
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);
        }
    }

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(DmccMurabhaStep::ClientWakala);

        $this->createStepHistories(
            $request,
            $traderOrder,
            DmccMurabhaStep::MurabhaOfferIssued
        );
    }

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(DmccMurabhaStep::ContractSigned);

        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        if ($request->boolean('automatically_generate_file')) {
            $trader->createSellingCommodityToCustomerDocument($traderOrder);
        } else {
            $traderOrder->addMediaFromBase64(
                base64_encode(file_get_contents($request->file('document')))
            )
                ->usingFileName("client-certificate-{$traderOrder->id}.pdf")
                ->toMediaCollection(TraderOrderMediaCollection::SellingCommodityToCustomer);

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
        }
    }

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(DmccMurabhaStep::MurabhaOfferIssued);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            DmccMurabhaStep::MurabahaSaleCompleted
        );

        $this->createStepHistories(
            $request,
            $traderOrder,
            DmccMurabhaStep::MurabahaSaleCompleted
        );

        if ($canUpdateOrderStatus) {
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
        }
    }
}
