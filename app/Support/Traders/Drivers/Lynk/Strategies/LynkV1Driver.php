<?php

namespace App\Support\Traders\Drivers\Lynk\Strategies;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Enums\CompanyMarketType;
use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\OrderCancellationStatus;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Exceptions\TraderException;
use App\Jobs\TraderOrder\AutoCompleteSell\ProcessAutoCompleteSell;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Services\GetSuitableCommodityTypesService;
use App\Services\TraderOrder\TimeLimitService;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\Traders\Clients\LynkClient;
use App\Support\Traders\Contracts\Deliverable;
use App\Support\Traders\Contracts\SellConfirmationCertifiable;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkCancelOrderAtLocalMarket;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkCancelTraderOrder;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkTransferOwnershipToCustomer;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

// TODO_LOCAL_MARKET need to review
class LynkV1Driver implements Deliverable, SellConfirmationCertifiable, TraderInterface
{
    use Localizable;
    use TraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    protected $provider = 'lynk';

    protected $version = 'v1';

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder, ?int $preferredCommodityTypeId = null): ?Model
    {

        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        $traderOrder = $this->createInitialTraderOrder($financingOrder, $preferredCommodityTypeId);
        $this->updateReferenceNumber($traderOrder);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $traderOrder;
    }

    private function createInitialTraderOrder(FinancingOrder $financingOrder, ?int $preferredCommodityTypeId = null): Model
    {
        return $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => $this->generateTemporaryReference($financingOrder),
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
            'commodity_type_id' => $preferredCommodityTypeId,
        ]);
    }

    private function generateTemporaryReference(Model $financingOrder): string
    {
        return Str::upper(Str::random(14)).$financingOrder->id;
    }

    private function generateFinalReferenceNumber(Model $traderOrder): string
    {
        return sprintf(
            'LYNK-%s-%s-%s',
            $traderOrder->financing_order_id,
            $traderOrder->id,
            $traderOrder->created_at->format('Ymd')
        );
    }

    private function updateReferenceNumber(Model $traderOrder): void
    {
        $referenceNumber = $this->generateFinalReferenceNumber($traderOrder);
        $traderOrder->update(['reference' => $referenceNumber]);
    }

    /**
     * @throws TraderException
     */
    public function processInitiatedTraderOrder(TraderOrder $traderOrder): TraderOrder
    {
        Log::channel('local_market')->info("Create New Order at Local Market For Trader Order id => {$traderOrder->id} and financing order => {$traderOrder->order->id}");
        $commodityData = (new GetSuitableCommodityTypesService($traderOrder))->resolve();
        LynkClient::of($traderOrder)->createOrder($commodityData['commodity_types_id'], $commodityData['force_commodity_type']);
        $traderOrder->update([
            'status' => TraderOrderStatus::InProgress,
            'force_commodity_type' => $commodityData['force_commodity_type'],
        ]);

        return $traderOrder;
    }

    public function createTransferOwnershipToLenderDocument(TraderOrder $traderOrder)
    {
        try {
            $this->withLocale('ar', function () use ($traderOrder) {
                $amount = $traderOrder->order->amount->convertAndFormatByDecimal(sperator: ',');
                $currentTimeInUtcTz = CarbonImmutable::now();
                $currentTimeInRiyadhTz = $currentTimeInUtcTz->timezone('Asia/Riyadh');
                $products = collect($traderOrder->products)->map(fn ($product) => LynkCommodityProductDto::fromArray($product));
                $this->setTimeLimitByType($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);
                $this->storeOrderDocumentAsPdf(
                    'local-commodity-market.transfer-ownership-to-lender',
                    [
                        'order_id' => $traderOrder->order->id,
                        'products' => $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products),
                        'reference_number' => $traderOrder->id,
                        'trader_order_reference' => $traderOrder->reference,
                        'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                        'order_number' => $traderOrder->financing_order_id,
                        'amount' => $amount,
                        'previous_owner' => $products->map(
                            fn ($item) => $item->getPreviousOwnerAsArray()
                        )
                            ->flatten()
                            ->implode('،'),
                        'product_name' => $products->implode(fn ($item) => $item->getProduct(), '،'),
                        'date' => $currentTimeInRiyadhTz->toDateString(),
                        'time' => $currentTimeInRiyadhTz->toTimeString(),
                        'trade_order' => $traderOrder,
                        'financing_order' => $traderOrder->order,
                    ],
                    $traderOrder,
                    TraderOrderMediaCollection::TransferOwnershipToLender
                );

                $this->createTraderOrderHistory(
                    $traderOrder,
                    FinancingOrderHistory::CreateTransferOwnershipToLenderDocument
                );
            });
        } catch (\Throwable $exception) {
            throw new TraderException(
                'Failed to create lender ownership certificate',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                ],
                $exception
            );
        }
    }

    public function createSellConfirmationDocument(TraderOrder $traderOrder): void
    {
        try {
            $trader = Trader::driver($traderOrder->provider);
            $financeOrder = $traderOrder->order;

            $trader->storeOrderDocumentAsPdf(
                'local-commodity-market.sell-confirmation-certificate',
                [
                    'products' => $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products, LynkCommodityProductDto::groupedByKeys()),
                    'trader_order_reference' => $traderOrder->reference,
                    'amount' => $financeOrder->amount->convertAndFormatByDecimal(sperator: ','),
                    'customer_name' => $financeOrder->customer_name,
                    'current_date' => saudi_now('Y-m-d'),
                    'current_time' => saudi_now('H:i:s'),
                ],
                $traderOrder,
                TraderOrderMediaCollection::SellConfirmationDocument,
            );

            $this->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachSellConfirmationDocument,
            );
        } catch (\Throwable $e) {
            Log::channel('local_market')->error('Failed to create sell-confirmation-certificate', [
                'message' => $e->getMessage(),
            ]);

            throw new TraderException(
                'Failed to create sell-confirmation-certificate',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                ],
                $e
            );
        }
    }

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder, $preferredCommodityTypeId = null): TraderOrder
    {
        return $this->getOrInitiateTraderOrder($financingOrder, $preferredCommodityTypeId);
    }

    public function getDefaultInitialTradeOrderStatus()
    {
        return TraderOrderStatus::Initiated;
    }

    public function createSellingCommodityToCustomerDocument(TraderOrder $traderOrder)
    {
        try {
            Log::info('Creating selling commodity to customer document', [
                'trader_order_id' => $traderOrder->id,
            ]);
            $this->withLocale('ar', function () use ($traderOrder) {
                $dateTime = $traderOrder->traderHistories()
                    ->where('action', FinancingOrderHistory::ContractSigned)
                    ->first()
                    ?->created_at;
                $currentTimeInUtcTz = CarbonImmutable::parse($dateTime);
                $currentTimeInRiyadhTz = $currentTimeInUtcTz->timezone('Asia/Riyadh');
                $amount = $traderOrder->order->selling_price->convertAndFormatByDecimal(sperator: ',');

                $customerName = $traderOrder->order->customer_name;

                $this->storeOrderDocumentAsPdf(
                    'local-commodity-market.selling-commodity-to-customer',
                    [
                        'reference_number' => $traderOrder->id,
                        'trader_order_reference' => $traderOrder->reference,
                        'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                        'order_number' => $traderOrder->financing_order_id,
                        'products' => $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products, LynkCommodityProductDto::groupedByKeys()),
                        'amount' => $amount,
                        'customer_name' => $customerName,
                        'contract_signed_date' => $currentTimeInRiyadhTz->toDateString(),
                        'contract_signed_time' => $currentTimeInRiyadhTz->toTimeString(),
                    ],
                    $traderOrder,
                    TraderOrderMediaCollection::SellingCommodityToCustomer,
                );
                $this->createTraderOrderHistory(
                    $traderOrder,
                    FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                    [
                        'created_at' => $currentTimeInUtcTz,
                    ]
                );
            });
        } catch (Exception $exception) {
            throw new TraderException(
                'Failed to create customer ownership document',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                ],
                $exception
            );
        }
    }

    public function sellCommodityToOpenMarket(TraderOrder $traderOrder)
    {
        $this->sellCommodityToLocalMarket($traderOrder);

        // TODO:: the history needs discussion
        //         $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);
    }

    public function sellCommodityToLocalMarket(TraderOrder $traderOrder)
    {
        LynkClient::of($traderOrder)->sellProduct();
    }

    public function cancelOrder(FinancingOrder $financingOrder): int
    {
        return OrderCancellationStatus::PendingCancellation;
    }

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area)
    {
        if ($traderOrder->status->is(TraderOrderStatus::Initiated) || $traderOrder->status->is(TraderOrderStatus::InProgress)) {
            return true;
        }

        return false;
    }

    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled,
        $cancelledByType = TraderOrderCancelType::System,
        ?User $cancelledBy = null
    ): int {
        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, $cancelReason, cancelledByType: $cancelledByType, cancelledBy: $cancelledBy);

        match ($traderOrder->mode) {
            TraderOrderMode::Manual => $this->handleManualOrderCancellation($traderOrder, $cancelReason, $cancelledByType, $cancelledBy),
            TraderOrderMode::Automatic => $this->handleAutomaticOrderCancellation($traderOrder, $cancelReason, $cancelledByType, $cancelledBy),
        };
        app(TimeLimitService::class)->cancelPendingTimeLimits($traderOrder);

        return TraderOrderCancellationStatus::Cancelled;
    }

    protected function handleManualOrderCancellation(
        TraderOrder $traderOrder,
        int $cancelReason,
        $cancelledByType,
        ?User $cancelledBy
    ): void {
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason);
        // use at cancel financing order
        if ($traderOrder->order->isInPendingCancellationState()) {
            app(CancelOrder::class)->handle($traderOrder->order, $cancelledBy);
        }
        if ($traderOrder->order->status->is(FinancingOrderStatus::InProgress) && $traderOrder->order->activeTraderOrder()->count() == 0) {
            $traderOrder->order->update(['status' => FinancingOrderStatus::PendingTraderOrder]);
        }
    }

    protected function handleAutomaticOrderCancellation(
        TraderOrder $traderOrder,
        int $cancelReason,
        $cancelledByType,
        ?User $cancelledBy
    ): void {
        ProcessLynkCancelOrderAtLocalMarket::dispatch($traderOrder->id, $cancelReason);
    }

    public function confirmCancelledFromProvider($traderOrder): void
    {
        Bus::chain([
            new ProcessLynkCancelTraderOrder($traderOrder->id),
            fn () => $this->updateFinancingOrderStatusAfterCancellation($traderOrder, $traderOrder->cancelDetail->cancel_reason->value),
            fn () => $this->retryOrder($traderOrder),
        ])->dispatch();
    }

    protected function updateFinancingOrderStatusAfterCancellation($traderOrder, int $cancelReason): void
    {
        $order = $traderOrder->order;
        $lender = $order->company->lender;
        if ($order->status->is(FinancingOrderStatus::PendingCancellation)) {
            $order->update(['status' => FinancingOrderStatus::Cancelled]);
        } elseif ($order->status->is(FinancingOrderStatus::InProgress)) {
            if (
                $lender->lenderDetail->preferred_market_type->is(CompanyMarketType::Local())
                && ($cancelReason == TraderOrderCancelReason::FailureToPurchase || $cancelReason == TraderOrderCancelReason::FailureToSellAtLocalMarket)
            ) {
                $order->update(['status' => FinancingOrderStatus::TradingFailure]);
            } else {
                $order->update(['status' => FinancingOrderStatus::PendingTraderOrder]);
            }
        }
    }

    protected function canRetryOrder(TraderOrder $traderOrder): bool
    {
        $lenderDetail = $traderOrder->order->company->lender->lenderDetail;

        // Check if the order can be retried based on several conditions:
        // 1. No previous trader orders with a commodity type exist for this order
        return ! $traderOrder->order->traderOrders()->whereNotNull('commodity_type_id')->exists() &&
            // 2. The current order has no commodity type assigned
            $traderOrder->order->commodity_type_id == null &&
            // 3. The lender's trading mode is set to automatic
            $lenderDetail->trading_mode->is(TraderOrderMode::Automatic) &&
            // 4. The lender's preferred market type is set to 'Any'
            $lenderDetail->preferred_market_type->is(CompanyMarketType::Any) && (
                // 5. The cancellation reason is either:
                // - No eligible commodities were available
                // - Failed to purchase
                $traderOrder->cancelDetail->cancel_reason->in([
                    TraderOrderCancelReason::NoEligibleCommoditiesAvailable,
                    TraderOrderCancelReason::FailureToPurchase,
                ])) &&
            // 6. There are no active trader orders for this order
            ! $traderOrder->order->activeTraderOrder()->exists();
    }

    public function retryOrder(TraderOrder $traderOrder): void
    {
        if ($this->canRetryOrder($traderOrder)) {
            if ($traderOrder->order->status->is(FinancingOrderStatus::PendingTraderOrder)) {
                $traderOrder->order->update(['status' => FinancingOrderStatus::InProgress]);
            }

            Trader::driver(\App\Enums\Trader::Bursam, 'v2')->createTraderOrder($traderOrder->order);
        }
    }

    /**
     * @return string <Driver>_<collectionName>_<companies.unique_name>_<financing_orders.id>_<trader_orders.reference_number>_YYYYMMDD.pdf
     */
    public function generatePdfFileName($traderOrder, $collectionName): string
    {
        $fileType = match ($collectionName) {
            TraderOrderMediaCollection::TransferOwnershipToLender => 'CommCert',
            TraderOrderMediaCollection::SellingCommodityToCustomer => 'BorrOwnCert',
            TraderOrderMediaCollection::LynkSalePledgeCertificate => 'SellCommCert',
            TraderOrderMediaCollection::SellConfirmationDocument => 'SellConfCert',
        };

        return 'LYNK_'.$fileType.'_'.$traderOrder->order->company->unique_name.'_'.$traderOrder->financing_order_id.'_'.$traderOrder->reference.'_'.date('Ymd').'.pdf';
    }

    public function processProceedContractSigned(TraderOrder $traderOrder): void {}

    // use it in public api to proceed order after purchasing commodity step by one step
    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        $request = request();
        $request['automatically_generate_file'] = true;
        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
            ->updateCommodityCertificateForClient($traderOrder, $request);
    }

    public function checkCanInitiateTraderOrder()
    {
        return true;
    }

    public function moveHoldTraderOrder(TraderOrder $trader)
    {
        return true;
    }

    public function hoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string
    {

        return match ($traderOrder->cancelDetail?->cancel_reason->value) {
            TraderOrderCancelReason::Manual => __('order.trader.lynk.cancelled_status'),
            TraderOrderCancelReason::NoEligibleCommoditiesAvailable => __('order.trader.lynk.no_commodity_available'),
            TraderOrderCancelReason::FailureToPurchase => __('order.trader.lynk.internal_technical_error'),
            TraderOrderCancelReason::TraderOrderIsCancelled => __('order.user_cancel_request'),
            TraderOrderCancelReason::FinancingOrderIsCancelled => __('order.user_cancel_order'),
            TraderOrderCancelReason::ExpiredContractSignTime => __('order.trader.lynk.expired_contract_time', [
                'TIME' => $traderOrder->getRecentTimeLimit(TraderOrderTimeLimitType::ContractSignTimeLimit, TraderOrderTimeLimitStatus::Expired)->default_value,
            ]),
            TraderOrderCancelReason::ExpiredConfirmationTimeLimit => __('order.trader.lynk.expired_confirmation_time_limit', [
                'TIME' => $traderOrder->getRecentTimeLimit(TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit, TraderOrderTimeLimitStatus::Expired)->default_value,
            ]),
            default => null,
        };
    }

    public function contractSignedMessage(TraderOrder $traderOrder)
    {
        if ($traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned)) {
            return match ($traderOrder->contract_signed_type->value) {
                ContractSignedType::Sell => __('order.trader.lynk.steps.contract_signed.v1.sell'),
                ContractSignedType::Delivery => __('order.trader.lynk.steps.contract_signed.v1.deliver'),
                default => null,
            };
        }

        return null;
    }

    public function clientWakalaMessage(TraderOrder $traderOrder)
    {
        return null;
    }

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
        $lastHistoryAction = (int) $traderOrder->traderHistories()->latest('id')->value('action');

        match ($traderOrder->mode) {
            TraderOrderMode::Automatic => $this->transitionFlowInAutomaticMode($traderOrder, $lastHistoryAction),
            TraderOrderMode::Manual => $this->transitionFlowInManualMode($traderOrder, $lastHistoryAction),
            default => null,
        };
    }

    protected function transitionFlowInManualMode(TraderOrder $traderOrder, int $lastHistoryAction): void
    {
        if ($lastHistoryAction === FinancingOrderHistory::ContractSigned) {
            $this->createSellingCommodityToCustomerDocument($traderOrder);
        } elseif (
            $lastHistoryAction === FinancingOrderHistory::CreateSellingCommodityToCustomerDocument &&
            $traderOrder->contract_signed_type->is(ContractSignedType::Sell)
        ) {
            $this->handleManualSellTransition($traderOrder);
        }
    }

    protected function transitionFlowInAutomaticMode(TraderOrder $traderOrder, int $lastHistoryAction): void
    {
        match ($lastHistoryAction) {
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAutoCompleteSell::dispatch($traderOrder->id),
            FinancingOrderHistory::ContractSigned => ProcessLynkTransferOwnershipToCustomer::dispatch($traderOrder->id),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessLynkSellingCommodityToOpenMarket::dispatch($traderOrder->id),
            default => null,
        };
    }

    protected function handleManualSellTransition(TraderOrder $traderOrder): void
    {
        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
            ->updateMurabhaCompleteDocument($traderOrder);
    }

    public function handleConfirmDelivery(TraderOrder $traderOrder)
    {
        LynkClient::of($traderOrder)->confirmDeliverProducts();
    }

    public function handleRequestDeliverCommodityToCustomer(TraderOrder $traderOrder)
    {
        $this->setTimeLimitByType($traderOrder, TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit);

        if ($traderOrder->mode == TraderOrderMode::Automatic) {
            LynkClient::of($traderOrder)->requestDeliverProducts();
        }
    }

    public function validateDeliverySequence(TraderOrder $traderOrder, bool $forceToProceed): void
    {
        $invalidSequence =
            $traderOrder->isPreviousStepNotCompleted(MurabhaStep::CustomerDeliveryConfirmation) ||
            (! $forceToProceed && $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder));

        if ($invalidSequence) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }
    }

    public function confirmDelivery(TraderOrder $traderOrder): bool
    {
        app(TimeLimitService::class)->cancelExpiry($traderOrder, TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit);

        return $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::CustomerDeliveryConfirmation
        );
    }

    // TODO: This can be refactored later once the BaseTrader class is added.
    //       The logic will then be implemented there, accepting $action as a second argument.
    public function isOrderInSellableState(TraderOrder $traderOrder): bool
    {
        return $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
    }

    public function isContractSignLimitEligibleForExpiry(TraderOrder $traderOrder): bool
    {
        return $traderOrder->doesLastActionMatchWith([FinancingOrderHistory::CreateTransferOwnershipToLenderDocument]);
    }

    protected function isCustomerDeliveryConfirmationStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderHistoryAction([FinancingOrderHistory::DeliveryCancelled, FinancingOrderHistory::DeliveryConfirmed]);
    }

    /**
     * Sets the time limit for a specific type based on the provided TraderOrder.
     *
     * @param  TraderOrder  $traderOrder  The TraderOrder for which the time limit needs to be set.
     * @param  mixed  $timeLimitType  The type of time limit to be set (DeliveryConfirmationTimeLimit or ContractSignTimeLimit).
     */
    private function setTimeLimitByType(TraderOrder $traderOrder, $timeLimitType): void
    {
        $timeLimitService = new TimeLimitService;

        match ($timeLimitType) {
            TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit => $timeLimitService->setDeliveryConfirmationTimeLimit($traderOrder),
            TraderOrderTimeLimitType::ContractSignTimeLimit => $timeLimitService->setContractSignTimeLimit($traderOrder),
            default => null,
        };
    }
}
