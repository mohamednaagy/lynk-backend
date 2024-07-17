<?php

namespace App\Support\Traders\TradingStrategies\Bursam;

use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// TODO_LOCAL_MARKET need to review

abstract class BaseLynkStrategy implements TraderStrategyInterface
{
    use TraderHelperTrait;

    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::TraderOrderCreated);

        app(UpdateTraderOrder::class)->handle($traderOrder, $request->validated());
        if ($traderOrder->mode == TraderOrderMode::Manual) {
            $traderOrder->update([
                'status' => TraderOrderStatus::InProgress,
            ]);
        }
        $this->createStepHistories(
            $request,
            $traderOrder,
            MurabhaStep::PurchasingCommodity
        );
        $this->transferOwnershipToLender($traderOrder, $request);
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

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::CommoditySoldToCustomer);

        $this->createStepHistories(
            $request,
            $traderOrder,
            MurabhaStep::MurabhaOfferIssued
        );
    }

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::PurchasingCommodity);

        $this->sellCommodityToCustomer($traderOrder, $request);
    }

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::CommoditySoldToCustomer);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::MurabahaSaleCompleted
        );

        $this->createStepHistories(
            $request,
            $traderOrder,
            MurabhaStep::MurabahaSaleCompleted
        );

        $trader = Trader::driver($traderOrder->provider);
        $currentTimeInUtcTz = CarbonImmutable::now();
        $currentTimeInRiyadhTz = $currentTimeInUtcTz->timezone('Asia/Riyadh');
        $financeOrder = $traderOrder->order;
        $trader->storeOrderDocumentAsPdf(
            'local-commodity-market.selling-pledge-certificate',
            [
                'products' => $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products),
                'amount' => $financeOrder->amount,
                'customer_name' => $financeOrder->customer_name,
                'current_date' => $currentTimeInRiyadhTz->toDateString(),
                'current_time' => $currentTimeInRiyadhTz->toTimeString(),
            ],
            $traderOrder,
            TraderOrderMediaCollection::LynkSalePledgeCertificate,
        );

        if ($canUpdateOrderStatus) {
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);

            //create fees for LYNK order at completed step
            app(DeductBalanceForNewOrder::class)->handle($traderOrder);

        }
    }

    protected function sellCommodityToCustomer($traderOrder, $request)
    {
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);
        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        $trader->createSellingCommodityToCustomerDocument($traderOrder);

        // automatic complete the order
        $this->updateMurabhaCompleteDocument($traderOrder, $request);
    }
}
