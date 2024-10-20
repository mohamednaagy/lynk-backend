<?php

namespace App\Support\Traders\Drivers\Lynk\Strategies;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\CompanyMarketType;
use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\OrderCancellationStatus;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\Traders\Clients\LynkClient;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

// TODO_LOCAL_MARKET need to review
class LynkV1Driver implements TraderInterface
{
    use Localizable;
    use TraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    protected $provider = 'lynk';

    protected $version = 'v1';

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {

        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        $traderOrder = $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => Str::upper(Str::random(14)).$financingOrder->id,
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
            'default_contract_sign_time_limit' => app(LocalMurabahaSettings::class)->default_contract_sign_time_limit,
        ]);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $traderOrder;
    }

    /**
     * @throws TraderException
     */
    public function processInitiatedTraderOrder(TraderOrder $traderOrder): TraderOrder
    {
        Log::channel('local_market')->info("Create New Order at Local Market For Trader Order id => {$traderOrder->id} and financing order => {$traderOrder->order->id}");
        LynkClient::of($traderOrder)->createOrder();

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
                $default_contract_sign_time_limit = app(LocalMurabahaSettings::class)->default_contract_sign_time_limit;

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
                        'default_contract_sign_time_limit'=> $default_contract_sign_time_limit
                    ],
                    $traderOrder,
                    TraderOrderMediaCollection::TransferOwnershipToLender
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

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder): TraderOrder
    {
        return $this->getOrInitiateTraderOrder($financingOrder);
    }

    public function getDefaultInitialTradeOrderStatus()
    {
        return TraderOrderStatus::Initiated;
    }

    public function createSellingCommodityToCustomerDocument(TraderOrder $traderOrder)
    {
        try {
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
                        'products' => $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products),
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

                $this->createTraderOrderHistory(
                    $traderOrder,
                    FinancingOrderHistory::PendingDelivery,
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
        // TODO_LOCAL_MARKET need to implement
    }

    public function cancelOrder(FinancingOrder $financingOrder): int
    {
        // TODO_LOCAL_MARKET need to implement
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
        $cancelledBy = null
    ): int {
        //        if ($traderOrder->mode == TraderOrderMode::Manual) {
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason, cancelledByType: $cancelledByType, cancelledBy: $cancelledBy);

        $order = $traderOrder->order;
        if ($order->isInPendingCancellationState()) {
            app(CancelOrder::class)->handle($order, auth()->user(), []);
        }

        if ($traderOrder->mode == TraderOrderMode::Automatic) {
            if ($traderOrder->order->company->preferred_market_type->is(CompanyMarketType::Local)) {
                $traderOrder->order->update([
                    'status' => FinancingOrderStatus::TradingFailure,
                ]);
            }

            if (

                $traderOrder->order->company->preferred_market_type->is(CompanyMarketType::Any)) {
                Trader::driver(\App\Enums\Trader::Bursam, 'v2')
                    ->createTraderOrder($traderOrder->order);
            }
            
        }

        if ($order->status->is(FinancingOrderStatus::InProgress)) {
            $order->update([
                'status' => FinancingOrderStatus::PendingTraderOrder,
            ]);
        }


        return TraderOrderCancellationStatus::Cancelled;
        //        }
    }

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void {}

    /**
     * @return string <Driver>_<collectionName>_<companies.unique_name>_<financing_orders.id>_<trader_orders.reference_number>_YYYYMMDD.pdf
     */
    public function generatePdfFileName($traderOrder, $collectionName): string
    {
        switch ($collectionName) {
            case 'transfer_ownership_to_lender':
                $fileType = 'CommCert';
                break;
            case 'selling_commodity_to_customer':
                $fileType = 'BorrOwnCert';
                break;
            case 'lynk_sale_pledge_certificate':
                $fileType = 'SellCommCert';
                break;
        }

        return 'LYNK_'.$fileType.'_'.$traderOrder->order->company->unique_name.'_'.$traderOrder->financing_order_id.'_'.$traderOrder->reference.'_'.date('Ymd').'.pdf';
    }

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

    public function HoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string
    {

        return match ($traderOrder->cancelDetail?->cancel_reason->value) {
            TraderOrderCancelReason::Manual => __('order.trader.lynk.cancelled_status'),
            TraderOrderCancelReason::NoEligibleCommoditiesAvailable => __('order.trader.lynk.no_commodity_available'),
            TraderOrderCancelReason::FailureToPurchase => __('order.trader.lynk.internal_technical_error'),
            TraderOrderCancelReason::ExpiredContractSignTime => __('order.trader.expired_contract_time', [
                'TIME' => $traderOrder->default_contract_sign_time_limit,
            ]),
            default => null,
        };
    }

    public function contractSignedMessage(TraderOrder $traderOrder)
    {
        return match ($traderOrder->contract_signed_type->value) {
            ContractSignedType::Sell => __('order.trader.lynk.steps.contract_signed.sell'),
            ContractSignedType::Delivery => __('order.trader.lynk.steps.contract_signed.deliver'),
            default => null,
        };
    }
}
