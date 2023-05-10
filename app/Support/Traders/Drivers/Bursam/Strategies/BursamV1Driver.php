<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\BursamMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Models\ProviderCredential;
use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Traits\BursamTraderHelperTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BursamV1Driver implements TraderInterface
{
    protected $provider = 'bursam';

    protected $version = 'v1';

    use BursamTraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    private ?string $accessToken;

    public function __construct()
    {
        $this->accessToken = ProviderCredential::where('provider_name', 'bursam')
            ->value('access_token');
    }

    public function baseUrl($path)
    {
        return 'http://'.config('trader.providers.bursam.base_url').'/'.$path;
    }

    /**
     * @return void
     */
    public function updateProviderCredential(): void
    {
        $response = Http::get($this->baseUrl('api/process/svc/auth/token'), [
            'grant_type' => config('trader.providers.bursam.grant_type'),
            'client_id' => config('trader.providers.bursam.member_short_name'),
            'client_secret' => config('trader.providers.bursam.client_secret_key'),
        ]);

        ProviderCredential::updateOrCreate(
            ['provider_name' => 'bursam'],
            ['access_token' => $response->json('access_token')],
        );
    }

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        return $financingOrder->traderOrders()->create([
            'data' => [
                'uuid_one' => Str::uuid(),
            ],
            'provider' => $this->provider,
            'reference' => '',
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
        ]);
    }

    public function createTraderOrder(FinancingOrder $financingOrder)
    {
        $traderOrder = $this->getOrInitiateTraderOrder($financingOrder);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/order.json'),
            $requestBody = [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder->uuid_one,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'bidOption' => 'Y',
                    'otcOption' => 'N',
                    'stbOption' => 'N',
                    'productCode' => 'CPO-MSIA-09',
                    'purchaseType' => 'P',
                    'clientName' => '',
                    'currency' => 'SAR',
                    'bidValue' => $financingOrder->amount->formatByDecimal(),
                    'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                    'tenor' => '00090',
                    'otcCounterParty' => $financingOrder->customer_name,
                    'otcMurabaha' => '',
                    'otcMurabahaValue' => $financingOrder->selling_price->formatByDecimal(),
                    'eCertNo' => '',
                ],
            ]
        );

        if (! empty($response->json('header.errorCode'))) {
            throw new TraderException(
                'Failed to create trader order',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->json(),
                    'financing_order_id' => $financingOrder->id,
                ]
            );
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);
        $traderOrder->update([
            'status' => TraderOrderStatus::InProgress,
        ]);
        $financingOrder->update([
            'status' => FinancingOrderStatus::InProgress,
        ]);

        return $response->json();
    }

    public function fetchOrderResultYNN(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/orderResult.json'),
            $requestBody = [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder->uuid_one,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'forceYN' => 'Y',
                    'maxWaitTime' => '10',
                    'waitAllDoneYN' => 'Y',
                ],
            ]
        );

        if ($response->json('status.processingCount') == 0 && ! empty($response->json('body.0.ecertNo'))) {
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiHoldingCertificateDocument);

            $products = [
                [
                    'product' => $response->json('body.0.productCode'),
                    'quantity' => $response->json('body.0.bidValue') / $response->json('body.0.price'),
                    'amount' => $response->json('body.0.bidValue'),
                    'currency' => $response->json('body.0.currency'),
                    'warehouse' => '--',
                    'owner' => 'LYNK',
                    'previous_owner' => 'LYNK',
                    'date_time_of_purchasing_commodity' => $response->json('body.0.purchaseTime'),
                    'warehouse_or_vault_emirates' => '--',
                    'warehouse_or_vault_country' => '--',
                    'uom' => $response->json('body.0.unit'),
                    'exchange_rate' => '1',
                ],
            ];

            $traderOrder->update([
                'original_data' => $response->json('body.0'),
                'products' => $products,
                'reference' => $response->json('body.0.ecertNo'),
            ]);
        } elseif (
            ($response->json('body.0.bidErrNo') != '999' && $response->json('status.processingCount') == 0)
            || $response->json('status.processingCount') > 0
        ) {
            throw new TraderException(
                'Failed to fetch order result YNN',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->json(),
                ]
            );
        }

        return $response->json();
    }

    public function getBidCertificateDetails(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/bidXML.json'),
            $requestBody = [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->reference,
                ],
            ]
        );

        if ($response->json('SUCCESSYN') == 'N') {
            throw new TraderException(
                'Failed to get bid certificate',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->json(),
                ]
            );
        }

        $traderOrder->update([
            'data' => [
                'products' => [
                    (new CommodityProductDto(
                        product: $response->json('PNAME'),
                        quantity: $response->json('PVOLUME'),
                        amount: (float) $response->json('TOTALVALUE'),
                        previous_owner: $response->json('OWNER'),
                        date_time_of_purchasing_commodity: $response->json('PURCHASETIMEDATE'),
                        uom: collect($traderOrder->original_data)->get('unit'),
                        currency: $response->json('CURRENCY')
                    ))->toArray(),
                ],
            ],
        ]);
        $bidOwnerShipTemplate = view('bursam-templates.bid-certificate-template', [
            'ecertno' => $response->json('ECERTNO'),
            'buyer' => $response->json('BUYER'),
            'owner' => $response->json('OWNER'),
            'bidno' => $response->json('BIDNO'),
            'totalvalue' => $response->json('TOTALVALUE'),
            'currency' => $response->json('CURRENCY'),
            'price' => $response->json('PRICE'),
            'price_myr_equivalent' => $response->json('PRICE_MYR_EQUIVALENT'),
            'purchase_timedate' => $response->json('PURCHASETIMEDATE'),
            'valuedate' => $response->json('VALUEDATE'),
            'pname' => $response->json('PNAME'),
            'pvolume' => $response->json('PVOLUME'),
            'line' => $response->json('LINE'),
        ])->render();

        $financingOrder = $traderOrder->order;
        PdfGenerator::outputFromHtml(
            $bidOwnerShipTemplate,
            function ($fileResource) use ($financingOrder, $traderOrder) {
                return $traderOrder
                    ->addMediaFromStream($fileResource)
                    ->usingFileName($financingOrder->getNationalId().'.pdf')
                    ->toMediaCollection(TraderOrderMediaCollection::TtiHoldingCertificate);
            }
        );

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::AttachTtiHoldingCertificateDocument);
    }

    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        try {
            $amount = $traderOrder->order->amount->formatByDecimal();

            $this->storeOrderDocumentAsPdf(
                'transfer-ownership-to-lender',
                [
                    'order_id' => $traderOrder->order->id,
                    'products' => $traderOrder->products,
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'amount' => $amount,
                    'previous_owner' => CommodityProductDto::fromArray($traderOrder->products[0])->getPreviousOwner(),
                    'product_name' => CommodityProductDto::fromArray($traderOrder->products[0])->getProduct(),
                    'date' => Carbon::now()->toDateString(),
                    'time' => Carbon::now()->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::TransferOwnershipToLender
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);
        } catch (\Throwable $exception) {
            throw new TraderException(
                'Failed to create lender ownership certificate',
                [
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
            $dateTime = $traderOrder->traderHistories()
                ->where('action', FinancingOrderHistory::ContractSigned)
                ->first()
                ?->created_at;

            $amount = $traderOrder->order->selling_price->formatByDecimal();

            $customerName = $traderOrder->order->customer_name;

            $this->storeOrderDocumentAsPdf(
                'selling-commodity-to-customer',
                [
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'products' => $traderOrder->products,
                    'amount' => $amount,
                    'customer_name' => $customerName,
                    'contract_signed_date' => $dateTime->toDateString(),
                    'contract_signed_time' => $dateTime->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::SellingCommodityToCustomer,
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
        } catch (Exception $exception) {
            throw new TraderException(
                'Failed to create customer ownership document',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                ],
                $exception
            );
        }
    }

    public function sellingCommodityToOpenMarket(TraderOrder $traderOrder)
    {
        if (! $traderOrder->uuid_two) {
            $traderOrder->update([
                'data' => [
                    'uuid_two' => Str::uuid(),
                ],
            ]);
        }

        $financingOrder = $traderOrder->order;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/order.json'),
            $requestBody = [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder->uuid_two,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'bidOption' => 'N',
                    'otcOption' => 'Y',
                    'stbOption' => 'Y',
                    'productCode' => 'CPO-MSIA-09', // get it from settings
                    'purchaseType' => 'P',
                    'clientName' => '',
                    'currency' => 'SAR',
                    'bidValue' => $financingOrder->amount->formatByDecimal(),
                    'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                    'tenor' => '00090',
                    'otcCounterParty' => $financingOrder->customer_name,
                    'otcMurabaha' => '',
                    'otcMurabahaValue' => $financingOrder->selling_price->formatByDecimal(),
                    'eCertNo' => $traderOrder->reference,
                ],
            ]
        );

        if (! empty($response->json('header.errorCode')) || $response->json('body.0.statusCode') != 0) {
            throw new TraderException(
                'Failed to sell commodity to market',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                    'provider_request_body' => $requestBody,

                ]
            );
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);
    }

    public function fetchOrderResultNYY(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/orderResult.json'),
            [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder->uuid_two,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'forceYN' => 'Y',
                    'maxWaitTime' => '10',
                    'waitAllDoneYN' => 'Y',
                ],
            ]
        );

        if (
            $response->json('status.processingCount') == 0
            && $response->json('body.0.otcErrNo') == '999'
            && $response->json('body.0.stbErrNo') == '999'
        ) {
            $this->createStepHistories(request(), $traderOrder, BursamMurabhaStep::MurabahaSaleCompleted);
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);

            $traderOrder->order->update([
                'status' => FinancingOrderStatus::Completed,
            ]);
        } else {
            // TODO: should we throw exception here?
            Log::error('bursam_provider', [
                'provider' => $traderOrder->provider,
                'version' => $traderOrder->version,
                'uuid_two' => $traderOrder->uuid_two,
                'fetchOrderResultNYY' => $response->json(),
            ]);
        }

        return $response->json();
    }

    public function getOtcCertificateDetails(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/otcXML.json'),
            $requestBody = [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->reference,
                ],
            ]
        );

        if ($response->json('SUCCESSYN') == 'N') {
            throw new TraderException(
                'Failed to get OTC certificate details',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                    'provider_request_body' => $requestBody,

                ]
            );
        }

        $otcOwnerShipTemplate = view('bursam-templates.otc-certificate-template', [
            'ecertno' => $response->json('ECERTNO'),
            'seller' => $response->json('SELLER'),
            'buyer' => $response->json('BUYER'),
            'murabaha_value' => $response->json('MURABAHAVALUE'),
            'total_value' => $response->json('TOTALVALUE'),
            'currency' => $response->json('CURRENCY'),
            'price' => $response->json('PRICE'),
            'price_myr_equivalent' => $response->json('PRICE_MYR_EQUIVALENT'),
            'reporting_time_date' => $response->json('REPORTINGTIMEDATE'),
            'value_date' => $response->json('VALUEDATE'),
            'p_name' => $response->json('PNAME'),
            'p_volume' => $response->json('PVOLUME'),
            'line' => $response->json('LINE'),
        ])->render();

        $financingOrder = $traderOrder->order;
        PdfGenerator::outputFromHtml(
            $otcOwnerShipTemplate,
            function ($fileResource) use ($financingOrder, $traderOrder) {
                return $traderOrder
                    ->addMediaFromStream($fileResource)
                    ->usingFileName($financingOrder->getNationalId().'.pdf')
                    ->toMediaCollection(TraderOrderMediaCollection::TtiHoldingCertificate);
            }
        );

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetOwnershipToCustomerCertificate);
    }

    public function getStbCertificateDetails(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/stbXML.json'),
            $requestBody = [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->reference,
                ],
            ]
        );

        if ($response->json('SUCCESSYN') == 'N') {
            throw new TraderException(
                'Failed to get STB certificate details',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_response_body' => $response->json(),
                    'provider_request_body' => $requestBody,

                ]
            );
        }

        $stpOwnerShipTemplate = view('bursam-templates.stp-certificate-template', [
            'ecertno' => $response->json('ECERTNO'),
            'seller' => $response->json('SELLER'),
            'buyer' => $response->json('BUYER'),
            'total_value' => $response->json('TOTALVALUE'),
            'currency' => $response->json('CURRENCY'),
            'price' => $response->json('PRICE'),
            'price_myr_equivalent' => $response->json('PRICE_MYR_EQUIVALENT'),
            'selling_time_date' => $response->json('SELLINGTIMEDATE'),
            'value_date' => $response->json('VALUEDATE'),
            'p_name' => $response->json('PNAME'),
            'p_volume' => $response->json('PVOLUME'),
            'line' => $response->json('LINE'),
        ])->render();

        $financingOrder = $traderOrder->order;
        PdfGenerator::outputFromHtml(
            $stpOwnerShipTemplate,
            function ($fileResource) use ($financingOrder, $traderOrder) {
                return $traderOrder
                    ->addMediaFromStream($fileResource)
                    ->usingFileName($financingOrder->getNationalId().'.pdf')
                    ->toMediaCollection(TraderOrderMediaCollection::TtiHoldingCertificate);
            }
        );
    }

    public function cancelOrder(FinancingOrder $financingOrder): mixed
    {
        // TODO: Implement cancelOrder() method.
        return '';
    }

    /**
     * @param  TraderOrder  $traderOrder
     * @return void
     */
    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
    }
}
