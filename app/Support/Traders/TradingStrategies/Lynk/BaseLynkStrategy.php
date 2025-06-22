<?php

namespace App\Support\Traders\TradingStrategies\Lynk;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        if (isset($data['auto_generate_financing_institution_certificate'])) {
            $trader = Trader::driver($traderOrder->provider, $traderOrder->version);
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
        $this->sellCommodityToCustomer($traderOrder, $request);
    }

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, array $data)
    {
        // Check if step is already complete
        $stepAlreadyComplete = $traderOrder->checkOrderStepComplete(MurabhaStep::MurabahaSaleCompleted);

        if ($stepAlreadyComplete) {
            Log::info('LynkStrategy updateMurabhaCompleteDocument: Step already completed', [
                'trader_order_id' => $traderOrder->id,
                'current_status' => $traderOrder->status->key,
                'last_action' => $traderOrder->traderHistories()->latest('id')->first()?->action,
            ]);

            // Check if the history record was created but observer wasn't triggered
            $lastMurabahaSaleHistory = $traderOrder->traderHistories()
                ->where('action', FinancingOrderHistory::MurabahaSaleCompleted)
                ->latest('id')
                ->first();

            if ($lastMurabahaSaleHistory) {
                Log::info('LynkStrategy updateMurabhaCompleteDocument: Manually triggering observer for existing history', [
                    'trader_order_id' => $traderOrder->id,
                    'history_id' => $lastMurabahaSaleHistory->id,
                ]);

                // Manually trigger the observer to ensure wallet charging happens
                app(\App\Observers\TraderHistoryObserver::class)->created($lastMurabahaSaleHistory);
            }

            // If step is complete but status is not, update it
            if ($traderOrder->status->is(TraderOrderStatus::InProgress)) {
                $traderOrder->update(['status' => TraderOrderStatus::Completed]);
                app(TimeLimitService::class)->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);
                Log::info('LynkStrategy updateMurabhaCompleteDocument: Updated status to completed for already completed step', [
                    'trader_order_id' => $traderOrder->id,
                ]);
            }

            return;
        }

        $traderOrder->ensureCanAccessStep(MurabhaStep::CommoditySoldToCustomer);

        $trader = Trader::driver($traderOrder->provider);
        $currentTimeInUtcTz = CarbonImmutable::now();
        $currentTimeInRiyadhTz = $currentTimeInUtcTz->timezone('Asia/Riyadh');
        $financeOrder = $traderOrder->order;
        $trader->storeOrderDocumentAsPdf(
            'local-commodity-market.selling-pledge-certificate',
            [
                'products' => $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products, LynkCommodityProductDto::groupedByKeys()),
                'trader_order_reference' => $traderOrder->reference,
                'amount' => $financeOrder->amount->convertAndFormatByDecimal(sperator: ','),
                'customer_name' => $financeOrder->customer_name,
                'current_date' => $currentTimeInRiyadhTz->toDateString(),
                'current_time' => $currentTimeInRiyadhTz->toTimeString(),
            ],
            $traderOrder,
            TraderOrderMediaCollection::LynkSalePledgeCertificate,
        );

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::MurabahaSaleCompleted
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
        } else {
            Log::error('LynkStrategy updateMurabhaCompleteDocument failed to update order status to completed', [
                'trader_order_id' => $traderOrder->id,
                'last_action' => $traderOrder->traderHistories()->latest('id')->first()->action,
            ]);
        }

        app(TimeLimitService::class)->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);
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

    /**
     * Confirms the delivery of a commodity to the customer for the given trader order.
     *
     * Depending on the trader order mode, this method ensures the order can proceed
     * to the 'CustomerDeliveryConfirmation' step, logs the delivery confirmation in
     * the order history, and delegates the delivery confirmation handling to the
     * appropriate trader driver.
     *
     * @param  TraderOrder  $traderOrder  The trader order for which the delivery is being confirmed.
     */
    public function confirmDeliverCommodityToCustomer(TraderOrder $traderOrder)
    {
        match ($traderOrder->mode) {
            TraderOrderMode::Automatic => Trader::driver($traderOrder->provider, $traderOrder->version)
                ->handleConfirmDelivery($traderOrder),
            TraderOrderMode::Manual => null,
        };
    }

    /**
     * Initiates the delivery process of a commodity to the customer for the given trader order.
     *
     * Depending on the trader order mode, this method ensures the order can proceed
     * to the 'CommoditySoldToCustomer' step, logs the pending delivery in the order history,
     * and delegates the delivery request handling to the appropriate trader driver.
     *
     * @param  TraderOrder  $traderOrder  The trader order for which the delivery is being requested.
     */
    public function requestDeliverCommodityToCustomer(TraderOrder $traderOrder)
    {
        Trader::driver($traderOrder->provider, $traderOrder->version)
            ->handleRequestDeliverCommodityToCustomer($traderOrder);
    }
}
