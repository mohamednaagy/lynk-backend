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
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// TODO_LOCAL_MARKET need to review

abstract class BaseLynkStrategy implements TraderStrategyInterface
{
    use TraderHelperTrait;

    public function updatePurchasingCommodity(TraderOrder $traderOrder, array $data)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::TraderOrderCreated);
        Log::channel(LOG_CHANNEL_LYNK)->info(formatLogTitle('LynkStrategy updatePurchasingCommodity: Updating trader order', $traderOrder), [
            'data' => $data,
        ]);
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
        // Idempotency check: If the step is already complete, don't process again
        if ($traderOrder->checkOrderStepComplete(MurabhaStep::MurabahaSaleCompleted)) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('LynkStrategy updateMurabhaCompleteDocument: Step already completed, skipping', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'current_status' => $traderOrder->status->key,
                'last_action' => $traderOrder->last_history_action,
            ]);

            // If step is complete but status is not, update it
            if ($traderOrder->status->is(TraderOrderStatus::InProgress)) {
                $traderOrder->update(['status' => TraderOrderStatus::Completed]);
                app(TimeLimitService::class)->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);
                log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('LynkStrategy updateMurabhaCompleteDocument: Updated status to completed for already completed step', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                ]);
            }

            return;
        }

        $traderOrder->ensureCanAccessStep(MurabhaStep::CommoditySoldToCustomer);

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
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLogTitle('LynkStrategy updateMurabhaCompleteDocument failed to update order status to completed', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'last_action' => $traderOrder->last_history_action,
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
