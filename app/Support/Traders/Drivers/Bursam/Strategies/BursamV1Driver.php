<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\BursamErrorCode;
use App\Enums\BursamProductCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\OrderCancellationStatus;
use App\Enums\TraderErrorCode;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessFinancingOrders;
use App\Jobs\General\ProcessProceedContractAndClientWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Clients\BursamClient\BursamClient;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        return $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => '',
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
        ]);
    }

    public function createHoldTraderOrder(FinancingOrder $financingOrder): ?Model
    {

        $traderOrder = $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => '',
            'status' => TraderOrderStatus::Hold,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
        ]);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::OnHold);

        return $traderOrder;
    }

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder): TraderOrder
    {
        if ($this->checkCanInitiateTraderOrder()) {
            return $this->getOrInitiateTraderOrder($financingOrder);
        } else {
            return $this->createHoldTraderOrder($financingOrder);
        }
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
        $productCode = $this->getUnusedProductCode($traderOrder->provider);
        $response = BursamClient::of($traderOrder)->buyProduct($productCode);

        if (! empty($response->json('header.errorCode'))) {
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

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        $traderOrder->update([
            'status' => TraderOrderStatus::InProgress,
            'product_code' => $productCode,
        ]);

        return $traderOrder;
    }

    public function moveHoldTraderOrder(TraderOrder $trader)
    {
        $trader->update(['status' => TraderOrderStatus::Initiated]);
        $trader->traderHistories()->create(['action' => FinancingOrderHistory::GetTtiId]);
        ProcessFinancingOrders::dispatch();
    }

    public function fetchOrderResultYNN(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)
            ->fetchBuyResult();

        if ($response->json('status.processingCount') == 0 && ($response->json('body.0.bidErrNo') == '999')) {
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiHoldingCertificateDocument);

            $traderOrder->update([
                'original_data' => $response->json('body.0'),
                'reference' => $response->json('body.0.ecertNo'),
            ]);
        } elseif (
            in_array($response->json('body.0.bidErrNo'), BursamErrorCode::UNAVAILABLE_PRODUCT_ERROR_CODES)
        ) {
            $unavailableProductCodes = Cache::get('bursam_unavailable_product_codes', []);
            $unavailableProductCodes[] = $response->json('body.0.productCode');
            Cache::put('bursam_unavailable_product_codes', $unavailableProductCodes, now()->addMinutes(30));

            throw new TraderException(
                'Failed to fetch order result YNN Insufficient Commodity',
                [
                    'trader_order_id' => $traderOrder->id,
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                    'failure_reason' => $response->json('body.0.bidMsg'),
                    'failure_code' => TraderErrorCode::INSUFFICIENT_COMMODITY,
                ]
            );
        } elseif (
            ($response->json('body.0.bidErrNo') != '999' && $response->json('status.processingCount') == 0)
            || $response->json('status.processingCount') > 0
        ) {
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

        return $response->json();
    }

    public function getBidCertificateDetails(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->getBidXml();

        if ($response->json('SUCCESSYN') == 'N') {
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
    }

    public function createTransferOwnershipToLenderDocument(TraderOrder $traderOrder)
    {
        try {
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

        if (! empty($response->json('header.errorCode')) || $response->json('body.0.statusCode') != 0) {
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

        return $response;
    }

    public function fetchOrderResultNYY(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->fetchSellResult($traderOrder->uuid_two);

        if (
            $response->json('status.processingCount') == 0
            && $response->json('body.0.otcErrNo') == '999'
            && $response->json('body.0.stbErrNo') == '999'
        ) {
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CommoditySoldToMarket);
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

        return $response->json();
    }

    public function getOtcCertificateDetails(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->getOtcXml();

        if ($response->json('SUCCESSYN') == 'N') {
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
    }

    public function getStbCertificateDetails(TraderOrder $traderOrder)
    {
        $response = BursamClient::of($traderOrder)->getStbXml();

        if ($response->json('SUCCESSYN') == 'N') {
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
    }

    /**
     * @throws TraderException
     */
    public function cancelOrder(FinancingOrder $financingOrder): int
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();

        $this->sellCommodityToBursam($traderOrder);

        $traderOrder->update([
            'status' => TraderOrderStatus::PendingCancellation,
        ]);

        ProcessBursamStbCertificateAfterCancellation::dispatch($traderOrder->id, TraderOrderCancelReason::Manual, user: auth()->user());

        return OrderCancellationStatus::PendingCancellation;
    }

    /**
     * @throws TraderException
     */
    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled
    ): int {
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason, user: auth()->user());

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

    // use it in public api to proceed order after purchasing commodity step by one step
    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);
    }

    public function HoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string
    {
        return match ($traderOrder->status->value) {
            TraderOrderStatus::Hold => __('order.trader.bursa.hold_status', ['TIME' => Carbon::parse(Config::get('services.bursam.market_opening_start_time'))->translatedFormat('h:i A')]),
            default => null,
        };
    }
}
