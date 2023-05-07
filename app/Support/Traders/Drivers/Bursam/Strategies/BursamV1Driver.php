<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Models\ProviderCredential;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Traits\BursamTraderHelperTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BursamV1Driver implements TraderInterface
{
    use BursamTraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    private ?string $accessToken;

    public function __construct()
    {
        $this->accessToken = ProviderCredential::where('provider_name', 'bursam')->value('access_token');
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

        // store token with provider name
        ProviderCredential::updateOrCreate(
            ['provider_name' => 'bursam'],
            ['access_token' => $response->json('access_token')],
        );
    }

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->initiateTraderOrder()->exists()) {
            return $financingOrder->initiateTraderOrder()->first();
        }

        return $financingOrder->traderOrders()->create([
            'uuid' => Str::uuid(),
            'provider' => 'bursam',
            'status' => TraderOrderStatus::Initiated,
            'version' => 'v1',
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
            [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder->uuid,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'bidOption' => 'Y',
                    'otcOption' => 'N',
                    'stbOption' => 'N',
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
                    'eCertNo' => '',
                ],
            ]
        );

        if (! empty($response->json('header.errorCode'))) {
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'createTraderOrder',
                'responseBody' => $response->json(),
                'financingOrderId' => $financingOrder->id,
            ]));
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

    public function fetchOrderResult(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/orderResult.json'),
            [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder?->uuid,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'forceYN' => 'Y',
                    'maxWaitTime' => '10',
                    'waitAllDoneYN' => 'Y',
                ],
            ]
        );

        // i think it's better to check on  ("bidMsg" => "OK")
        if ($response->json('status.processingCount') == 0 && ! empty($response->json('body.0.ecertNo'))) {
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiHoldingCertificateDocument);
            $traderOrder->update([
                'data' => $response->json('body.0'),
                'reference' => $response->json('body.0.ecertNo'),
            ]);
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
            [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->ecertNo,
                ],
            ]
        );

        if ($response->json('SUCCESSYN') == 'N') {
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'getBidCertificateDetails',
                'responseBody' => $response->json(),
            ]));
        }

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

    public function transferOwnershipToLender($traderOrder): void
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
                    'previous_owner' => 'LYNK',
                    'product_name' => $traderOrder->productCode,
                    'unit' => $traderOrder->unit,
                    'bidValue' => $traderOrder->bidValue,
                    'date' => Carbon::now()->toDateString(),
                    'time' => Carbon::now()->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::TransferOwnershipToLender
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);
        } catch (\Exception $exception) {
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'createTransferOwnershipToLenderDocument',
                'requestBody' => [
                    'traderOrder' => $traderOrder,
                ],
                'responseBody' => $exception->getMessage(),
            ]));
        }
    }

    public function transferOwnershipToCustomer(TraderOrder $traderOrder)
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
                    'products' => [
                        [
                            'product' => $traderOrder->productCode,
                            'quantity' => $traderOrder->unit,
                            'uom' => '',
                            'amount' => $traderOrder->bidValue,
                            'warehouse' => '-',
                        ],
                    ],
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
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'createSellingCommodityToCustomerDocument',
                'requestBody' => [
                    'traderOrder' => $traderOrder,
                ],
                'responseBody' => $exception->getMessage(),
            ]), $exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function getOtcCertificateDetails(TraderOrder $traderOrder)
    {
        // this can be done after n,y,y. so we can generate it internally then get
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/otcXML.json'),
            [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->ecertNo,
                ],
            ]
        );

        if ($response->json('SUCCESSYN') == 'N') {
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'getOtcCertificateDetails',
                'responseBody' => $response->json(),
            ]));
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
    }

    public function getStbCertificateDetails(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/stbXML.json'),
            [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->ecertNo,
                ],
            ]
        );

        if ($response->json('SUCCESSYN') == 'N') {
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'getOtcCertificateDetails',
                'responseBody' => $response->json(),
            ]));
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

    public function sellingCommodityToOpenMarket(TraderOrder $traderOrder)
    {
        $traderOrder->update(['uuid' => Str::uuid()]);
        $financingOrder = $traderOrder->order;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseUrl('api/process/svc/bsas/order.json'),
            [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $traderOrder->uuid,
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
                    'valueDate' => now()->format('Ymd'),
                    'tenor' => '00090',
                    'otcCounterParty' => $financingOrder->customer_name,
                    'otcMurabaha' => '',
                    'otcMurabahaValue' => $financingOrder->selling_price->formatByDecimal(),
                    'eCertNo' => $traderOrder->ecertNo,
                ],
            ]
        );

        if (! empty($response->json('errorCode'))) {
            throw new TraderException(collect([
                'driver' => 'bursam',
                'step' => 'sellingCommodityToOpenMarket',
                'responseBody' => $response->json(),
            ]));
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::MurabahaSaleCompleted);
        $traderOrder->update([
            'status' => TraderOrderStatus::Completed,
        ]);
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
