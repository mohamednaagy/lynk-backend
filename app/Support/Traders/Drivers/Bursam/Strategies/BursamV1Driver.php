<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Enums\BursamProductCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\OrderCancellationStatus;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessProceedContractAndClientWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Services\GetSuitableCommodityTypesService;
use App\Services\TraderOrder\TimeLimitService;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Clients\BursamClient;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamInitiatedTraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

class BursamV1Driver implements TraderInterface
{
    use Localizable;
    use TraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    protected $provider = 'bursam';

    protected $version = 'v1';

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder, ?int $preferredCommodityTypeId = null): ?Model
    {
        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        $traderOrder = $this->createBaseTraderOrder($financingOrder, TraderOrderStatus::Initiated, $preferredCommodityTypeId);

        return $traderOrder;
    }

    protected function calculateTimeDifference()
    {
        $marketEndTime = env('BURSAM_MARKET_OPENING_END_TIME');
        $marketEnd = Carbon::createFromFormat('H:i:s', $marketEndTime);
        // Calculate the difference in hours
        $now = Carbon::now('Asia/Riyadh');
        $differenceInMinutes = $now->diffInMinutes($marketEnd);
        $differenceInHours = $differenceInMinutes / 60;

        return $differenceInHours;
    }

    public function createHoldTraderOrder(FinancingOrder $financingOrder, ?int $preferredCommodityTypeId = null): ?Model
    {
        $traderOrder = $this->createBaseTraderOrder($financingOrder, TraderOrderStatus::Hold, $preferredCommodityTypeId);

        // If it's a hold order, create history entry
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::OnHold);

        return $traderOrder;
    }

    public function createBaseTraderOrder(FinancingOrder $financingOrder, string $status, ?int $preferredCommodityTypeId = null): TraderOrder
    {
        // Create the base trader order
        $data = [
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => '',
            'status' => $status,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
            'commodity_type_id' => $preferredCommodityTypeId,

        ];

        // Create and return the order
        return $financingOrder->traderOrders()->create($data);
    }

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder, ?int $preferredCommodityTypeId = null): TraderOrder
    {
        // Determine the status of the order
        $status = $this->checkCanInitiateTraderOrder() ? TraderOrderStatus::Initiated : TraderOrderStatus::Hold;
        // Call the appropriate method based on the status
        if ($status === TraderOrderStatus::Hold) {
            return $this->createHoldTraderOrder($financingOrder, $preferredCommodityTypeId);
        }

        return $this->getOrInitiateTraderOrder($financingOrder, $preferredCommodityTypeId);
    }

    public function checkCanInitiateTraderOrder()
    {
        if (is_bursam_service_available()) {
            return true;
        }

        return false;
    }

    public function getDefaultInitialTradeOrderStatus()
    {
        return TraderOrderStatus::InProgress;
    }

    /**
     * @throws TraderException
     */
    public function processInitiatedTraderOrder(TraderOrder $traderOrder): TraderOrder
    {
        $commoditiesData = (new GetSuitableCommodityTypesService($traderOrder))->resolve();
        // we always use the first commodity type from the list
        $productCode = $commoditiesData['commodity_types_id'][0];
        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Using the first commodity type from the list as the product code for the trader order', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id, 
            'traderOrderId' => $traderOrder->id,
            'product_code' => $productCode,
        ]);
        $response = BursamClient::of($traderOrder)->buyProduct($productCode);
        $isValidResponse = BursamClient::of($traderOrder)->isValidResponse($response, 'buy_product');
        if ($isValidResponse) {
            $traderOrder->update([
                'status' => TraderOrderStatus::InProgress,
                'product_code' => $productCode,
            ]);
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

            return $traderOrder;
        } else {
            throw new TraderException(
                'Failed to create trader order',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'provider_response_body' => $response->json(),
                    'financing_order_id' => $traderOrder->order->id,
                    'failure_reason' => $response->json('body.0.bidMsg'),
                ]
            );
        }

    }

    public function moveHoldTraderOrder(TraderOrder $traderOrder)
    {
        $checkCanChangeStatusOfTrader = $this->checkCanInitiateTraderOrder();
        if ($checkCanChangeStatusOfTrader) {
            $traderOrder->update(['status' => TraderOrderStatus::Initiated]);
            ProcessBursamInitiatedTraderOrder::dispatch($traderOrder->id);
        }
    }

    public function fetchOrderResultYNN(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->fetchBuyResult();
        $isValidResponse = BursamClient::of($traderOrder)->validateFetchYNN($response);
        if ($isValidResponse) {
            $traderOrder->update([
                'original_data' => $response->json('body.0'),
                'reference' => $response->json('body.0.ecertNo'),
            ]);
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiHoldingCertificateDocument);

            return $response->json();
        } else {
            throw new TraderException(
                'Failed to fetch order result YNN',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                    'failure_reason' => $response->json('body.0.bidMsg'),
                    'failure_code' => $response->json('body.0.bidErrNo'),
                ]
            );
        }

    }

    public function getBidCertificateDetails(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->getBidXml();
        $isValidResponse = BursamClient::of($traderOrder)->isValidResponse($response, 'get_bid_certificate_details');
        if ($isValidResponse) {
            $currentTimeInUtcTz = CarbonImmutable::now();
            $traderOrder->update([
                'products' => [
                    (new CommodityProductDto(
                        product: $response->json('PNAME'),
                        quantity: $response->json('PVOLUME'),
                        amount: number_unformat($response->json('TOTALVALUE')),
                        previous_owner: Arr::pluck($response->json('LINE'), 'SUPPLIER'),
                        date_time_of_purchasing_commodity: $response->json('PURCHASETIMEDATE'),
                        uom: collect($traderOrder->original_data)->get('unit'),
                        currency: $response->json('CURRENCY')
                    ))->toArray(),
                ],
                'original_bid' => $response->json(),
            ]);

            $productName = $response->json('PNAME');
            $bidOwnerShipTemplate = view('bursam-templates.bid-certificate-template', [
                'e_cert_no' => $response->json('ECERTNO'),
                'buyer' => $response->json('BUYER'),
                'owner' => $response->json('OWNER'),
                'bid_no' => $response->json('BIDNO'),
                'total_value' => number_unformat($response->json('TOTALVALUE')),
                'total_value_myr_equivalent' => number_unformat($response->json('PRICE_MYR_EQUIVALENT')) * number_unformat($response->json('PVOLUME')),
                'currency' => $response->json('CURRENCY'),
                'purchase_time_date' => $response->json('PURCHASETIMEDATE').'  Malaysia Time (MYT)',
                'value_date' => $response->json('VALUEDATE').'  Malaysia Time (MYT)',
                'p_name' => in_array($productName, BursamProductCode::getValues())
                    ? BursamProductCode::fromValue($productName)->description
                    : $productName,
                'p_volume' => $response->json('PVOLUME'),
                'line' => $response->json('LINE'),
            ])->render();

            PdfGenerator::outputFromHtml(
                $bidOwnerShipTemplate,
                function ($fileResource) use ($traderOrder, $currentTimeInUtcTz) {
                    return $traderOrder
                        ->addMediaFromStream($fileResource)
                        ->usingFileName("holding-cert-{$traderOrder->reference}-{$currentTimeInUtcTz->toDateTimeString()}.pdf")
                        ->toMediaCollection(TraderOrderMediaCollection::TtiHoldingCertificate);
                }
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::AttachTtiHoldingCertificateDocument);
            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => Bid certificate generated successfully', $traderOrder) , [
                'financingOrderId' => $traderOrder->financing_order_id, 
                'traderOrderId' => $traderOrder->id
            ]);
        } else {
            throw new TraderException(
                'Failed to get bid certificate',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                ]
            );
        }

    }

    public function createTransferOwnershipToLenderDocument(TraderOrder $traderOrder)
    {
        try {
            $timeLimitService = new TimeLimitService;
            $timeLimitService->setContractSignTimeLimit($traderOrder);

            $this->withLocale('ar', function () use ($traderOrder) {
                $amount = $traderOrder->order->amount->convertAndFormatByDecimal(sperator: ',');
                $currentTimeInUtcTz = CarbonImmutable::now();
                $currentTimeInRiyadhTz = $currentTimeInUtcTz->timezone('Asia/Riyadh');
                $products = collect($traderOrder->products)->map(fn ($product) => CommodityProductDto::fromArray($product));

                $this->storeOrderDocumentAsPdf(
                    'transfer-ownership-to-lender',
                    [
                        'order_id' => $traderOrder->order->id,
                        'products' => $this->transformProductsToCommodityProductsDTO($traderOrder->products),
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
                    ],
                    $traderOrder,
                    TraderOrderMediaCollection::TransferOwnershipToLender
                );

                $this->createTraderOrderHistory(
                    $traderOrder,
                    FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
                    [
                        'created_at' => $currentTimeInUtcTz,
                    ]
                );

            });
            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => transferOwnershipToLenderDocument certificate generated successfully', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id, 
                'traderOrderId' => $traderOrder->id
            ]);

        } catch (\Throwable $exception) {
            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('exception of transfer ownership to lender', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id, 
                'traderOrderId' => $traderOrder->id, 
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new TraderException(
                'Failed to create lender ownership certificate',
                [
                    'financingOrderId' => $traderOrder->financing_order_id, 
                    'traderOrderId' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'error_message' => $exception->getMessage(),
                    'error_code' => $exception->getCode(),
                    'error_file' => $exception->getFile(),
                    'error_line' => $exception->getLine(),
                    'error_trace' => $exception->getTraceAsString(),
                ],
                $exception
            );
        }
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
                    'selling-commodity-to-customer',
                    [
                        'reference_number' => $traderOrder->id,
                        'trader_order_reference' => $traderOrder->reference,
                        'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                        'order_number' => $traderOrder->financing_order_id,
                        'products' => $this->transformProductsToCommodityProductsDTO($traderOrder->products),
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
        $this->sellCommodityToBursam($traderOrder);

        // TODO:: the history needs discussion
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);
    }

    public function sellCommodityToBursam(TraderOrder $traderOrder)
    {
        if (! $traderOrder->uuid_two) {
            $traderOrder->update([
                'uuid_two' => Str::uuid(),
            ]);
        }

        $response = BursamClient::of($traderOrder)->sellProduct();
        $isValidResponse = BursamClient::of($traderOrder)->isValidResponse($response, 'sell_product');
        if ($isValidResponse) {
            return $response;
        } else {
            throw new TraderException(
                'Failed to sell commodity to market',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                ]
            );
        }

    }

    public function fetchOrderResultNYY(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->fetchSellResult($traderOrder->uuid_two);
        $isValidResponse = BursamClient::of($traderOrder)->validateFetchNYY($response);
        if ($isValidResponse) {
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CommoditySoldToMarket);

            return $response->json();
        } else {
            throw new TraderException(
                'Failed to fetch order result NYY',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'uuid_two' => $traderOrder->uuid_two,
                    'provider_response_body' => $response->json(),
                ]
            );
        }

    }

    public function getOtcCertificateDetails(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->getOtcXml();
        $isValidResponse = BursamClient::of($traderOrder)->isValidResponse($response, 'otc_certificate_details');
        if ($isValidResponse) {
            $traderOrder->update([
                'otc_data' => $response->json(),
            ]);

            $currentTimeInUtcTz = CarbonImmutable::now();
            $productName = $response->json('PNAME');
            $otcOwnerShipTemplate = view('bursam-templates.otc-certificate-template', [
                'e_cert_no' => $response->json('ECERTNO'),
                'seller' => $response->json('SELLER'),
                'buyer' => $response->json('BUYER'),
                'murabaha_value' => $response->json('MURABAHAVALUE'),
                'total_value' => number_unformat($response->json('TOTALVALUE')),
                'total_value_myr_equivalent' => number_unformat($response->json('PRICE_MYR_EQUIVALENT')) * number_unformat($response->json('PVOLUME')),
                'currency' => $response->json('CURRENCY'),
                //            'price' => $response->json('PRICE'),
                //            'price_myr_equivalent' => $response->json('PRICE_MYR_EQUIVALENT'),
                'reporting_time_date' => $response->json('REPORTINGTIMEDATE').'  Malaysia Time (MYT)',
                'value_date' => $response->json('VALUEDATE').'  Malaysia Time (MYT)',
                'p_name' => in_array($productName, BursamProductCode::getValues())
                    ? BursamProductCode::fromValue($productName)->description
                    : $productName,
                'p_volume' => $response->json('PVOLUME'),
                'line' => $response->json('LINE'),
            ])->render();

            PdfGenerator::outputFromHtml(
                $otcOwnerShipTemplate,
                function ($fileResource) use ($traderOrder, $currentTimeInUtcTz) {
                    return $traderOrder
                        ->addMediaFromStream($fileResource)
                        ->usingFileName("otc-cert-{$traderOrder->reference}-{$currentTimeInUtcTz->toDateTimeString()}.pdf")
                        ->toMediaCollection(TraderOrderMediaCollection::BursamSellingCommodityToCustomer);
                }
            );

            $this->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetOwnershipToCustomerCertificate,
                [
                    'created_at' => $currentTimeInUtcTz,
                ]
            );
        } else {
            throw new TraderException(
                'Failed to get OTC certificate details',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                ]
            );
        }

    }

    public function getStbCertificateDetails(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->getStbXml();
        $isValidResponse = BursamClient::of($traderOrder)->isValidResponse($response, 'stb_certificate_details');
        if ($isValidResponse) {
            $traderOrder->update([
                'original_stb' => $response->json(),
            ]);

            $currentTimeInUtcTz = CarbonImmutable::now();
            $productName = $response->json('PNAME');
            $stbOwnerShipTemplate = view('bursam-templates.stb-certificate-template', [
                'e_cert_no' => $response->json('ECERTNO'),
                'seller' => $response->json('SELLER'),
                'buyer' => $response->json('BUYER'),
                'total_value' => number_unformat($response->json('TOTALVALUE')),
                'total_value_myr_equivalent' => number_unformat($response->json('PRICE_MYR_EQUIVALENT')) * number_unformat($response->json('PVOLUME')),
                'currency' => $response->json('CURRENCY'),
                //            'price' => $response->json('PRICE'),
                //            'price_myr_equivalent' => $response->json('PRICE_MYR_EQUIVALENT'),
                'selling_time_date' => $response->json('SELLINGTIMEDATE').'  Malaysia Time (MYT)',
                'value_date' => $response->json('VALUEDATE').'  Malaysia Date (MYT)',
                'p_name' => in_array($productName, BursamProductCode::getValues())
                    ? BursamProductCode::fromValue($productName)->description
                    : $productName,
                'p_volume' => $response->json('PVOLUME'),
                'line' => $response->json('LINE'),
            ])->render();

            PdfGenerator::outputFromHtml(
                $stbOwnerShipTemplate,
                function ($fileResource) use ($traderOrder, $currentTimeInUtcTz) {
                    return $traderOrder
                        ->addMediaFromStream($fileResource)
                        ->usingFileName("stb-cert-{$traderOrder->reference}-{$currentTimeInUtcTz->toDateTimeString()}.pdf")
                        ->toMediaCollection(TraderOrderMediaCollection::BursamTtiHoldingCertificate);
                }
            );

            $this->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetSellingToMarketCertificate,
                [
                    'created_at' => $currentTimeInUtcTz,
                ]
            );
        } else {
            throw new TraderException(
                'Failed to get STB certificate details',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),

                ]
            );
        }

    }

    /**
     * @throws TraderException
     */
    public function cancelOrder(FinancingOrder $financingOrder): int
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();

        $this->sellCommodityToBursam($traderOrder);

        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::Manual, cancelledByType: TraderOrderCancelType::User, cancelledBy: auth()->user());

        ProcessBursamStbCertificateAfterCancellation::dispatch($traderOrder->id, TraderOrderCancelReason::Manual, TraderOrderCancelType::User,
            auth()->user());

        app(FireWebhookWhenStatusIsCancelled::class)->handle($traderOrder);

        return OrderCancellationStatus::PendingCancellation;
    }

    /**
     * @throws TraderException
     */
    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled,
        $cancelledByType = TraderOrderCancelType::System,
        ?User $cancelledBy = null
    ): int {
        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, $cancelReason, cancelledByType: $cancelledByType, cancelledBy: $cancelledBy);

        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason);

        $activeTraderOrdersCount = TraderOrder::where('status', TraderOrderStatus::InProgress)
            ->where('financing_order_id', $traderOrder->id)
            ->count();

        if ($activeTraderOrdersCount !== 0) {
            return false;
        }

        $order = $traderOrder->order;

        if ($order->status->is(FinancingOrderStatus::PendingCancellation)) {
            $order->update([
                'status' => FinancingOrderStatus::Cancelled,
            ]);
        }

        if ($order->status->is(FinancingOrderStatus::InProgress)) {
            $order->update([
                'status' => FinancingOrderStatus::PendingTraderOrder,
            ]);
        }
        app(FireWebhookWhenStatusIsCancelled::class)->handle($traderOrder);
        app(TimeLimitService::class)->cancelPendingTimeLimits($traderOrder);

        return TraderOrderCancellationStatus::Cancelled;
    }

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void {}

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area)
    {
        if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
            return false;
        }

        return true;
    }

    /**
     * @return string <Driver>_<trader_orders.reference_number>.pdf
     */
    public function generatePdfFileName($traderOrder, $collectionName): string
    {
        return $traderOrder->provider.'-'.$traderOrder->reference.'.pdf';
    }

    public function processProceedContractSigned(TraderOrder $traderOrder): void
    {
        app()->make(TimeLimitService::class)->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);
    }

    // use it in public api to proceed order after purchasing commodity step by one step
    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);
    }

    public function hoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string
    {
        return match ($traderOrder->status->value) {
            TraderOrderStatus::Cancelled => $this->getCancellationReasonMessage($traderOrder->cancelDetail->cancel_reason->value) ,
            TraderOrderStatus::Hold => __('order.trader.bursa.hold_status', ['TIME' => Carbon::parse(Config::get('services.bursam.market_opening_start_time'))->translatedFormat('h:i A')]),
            default => null,
        };
    }

    public function getCancellationReasonMessage($reason)
    {
        return match ($reason) {
            TraderOrderCancelReason::FailureToPurchase => __('order.trader.lynk.internal_technical_error'),
            TraderOrderCancelReason::TraderOrderIsCancelled => __('order.user_cancel_request'),
            TraderOrderCancelReason::FinancingOrderIsCancelled => __('order.user_cancel_order'),
            TraderOrderCancelReason::MurabhaTimeout => __('order.murabaha_time_out'),
            TraderOrderCancelReason::NoEligibleCommoditiesAvailable => __('order.no_eligible_commodities_available'),

            default => null,
        };
    }

    public function contractSignedMessage(TraderOrder $traderOrder): ?string
    {
        return null;
    }

    public function clientWakalaMessage(TraderOrder $traderOrder): ?string
    {
        return null;
    }

    // TODO: This can be refactored later once the BaseTrader class is added.
    //       The logic will then be implemented there, accepting $action as a second argument.
    public function isOrderInSellableState(TraderOrder $traderOrder): bool
    {
        return $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ClientWakalaAccepted);
    }

    public function isContractSignLimitEligibleForExpiry(TraderOrder $traderOrder): bool
    {
        return $traderOrder->doesLastActionMatchWith([FinancingOrderHistory::CreateTransferOwnershipToLenderDocument]);
    }

    public function confirmCancelledFromProvider(TraderOrder $traderOrder): void {}

    public function handleConfirmDelivery(TraderOrder $traderOrder) {}

    public function handleRequestDeliverCommodityToCustomer(TraderOrder $traderOrder) {}
}
