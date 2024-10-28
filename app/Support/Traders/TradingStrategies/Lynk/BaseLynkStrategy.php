<?php

namespace App\Support\Traders\TradingStrategies\Lynk;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
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

    public function updatePurchasingCommodity(TraderOrder $traderOrder, array $data)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::TraderOrderCreated);

        app(UpdateTraderOrder::class)->handle($traderOrder, $data);
        $traderOrder->update([
            'status' => TraderOrderStatus::InProgress,
        ]);

        $this->transferOwnershipToLender($traderOrder, $data);

        $this->createStepHistories(
            $data,
            $traderOrder,
            MurabhaStep::PurchasingCommodity
        );

    }

    protected function transferOwnershipToLender(TraderOrder $traderOrder, $data)
    {
        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        // this (if) is a special case doesn't exist in history map
        if (isset($data['auto_generate_financing_institution_certificate'])) {
            $trader->createTransferOwnershipToLenderDocument($traderOrder);
        }
    }

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::CommoditySoldToCustomer);

        $this->createStepHistories(
            $request->validated(),
            $traderOrder,
            MurabhaStep::MurabhaOfferIssued
        );
    }

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::PurchasingCommodity);

        $this->sellCommodityToCustomer($traderOrder, $request->validated());
    }

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, array $data)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::CommoditySoldToCustomer);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
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
                'trader_order_reference' => $traderOrder->reference,
                'amount' => $financeOrder->amount->convertAndFormatByDecimal(sperator: ','),
                'customer_name' => $financeOrder->customer_name,
                'current_date' => $currentTimeInRiyadhTz->toDateString(),
                'current_time' => $currentTimeInRiyadhTz->toTimeString(),
            ],
            $traderOrder,
            TraderOrderMediaCollection::LynkSalePledgeCertificate,
        );
        $this->createStepHistories(
            $data,
            $traderOrder,
            MurabhaStep::MurabahaSaleCompleted
        );

        if ($canUpdateOrderStatus) {
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
        }
    }

    protected function sellCommodityToCustomer($traderOrder, $request)
    {
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);
    }

    public function updateSellConfirmationDocument(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::MurabahaSaleCompleted);
        $sellCOnfirmationDocumentFile = $request->file('sell_confirmation_document');
        $this->attachDocumentToOrder(
            $traderOrder,
            base64_encode(file_get_contents($sellCOnfirmationDocumentFile)),
            TraderOrderMediaCollection::SellConfirmationDocument,
            'base64',
            $sellCOnfirmationDocumentFile->getClientOriginalName(),
        );

        $this->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::AttachSellConfirmationDocument,
        );
    }
}
