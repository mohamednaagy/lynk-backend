<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\FinancingOrder;
use App\Models\ProviderCredential;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamOrderResult;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamTransferOwnershipToLender;
use App\Support\Traders\Traits\BursamTraderHelperTrait;
use Carbon\Carbon;
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

    public function baseURL($path)
    {
        return 'https://'.config('trader.providers.bursam.base_prod_url').'/'.$path;
    }

    public function baseDevURL($path)
    {
        return 'http://'.config('trader.providers.bursam.base_dev_url').'/'.$path;
    }

    /**
     * @return void
     */
    public function updateProviderCredential(): void
    {
        $response = Http::get($this->baseDevURL('api/process/svc/auth/token'), [
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
        ]);
    }

    public function createTraderOrder(FinancingOrder $financingOrder)
    {
        $traderOrder = $this->getOrInitiateTraderOrder($financingOrder);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseDevURL('api/process/svc/bsas/order.json'),
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
                    'valueDate' => $financingOrder->created_at->format('Ymd'),
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
    }

    public function fetchOrderResult(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseDevURL('api/process/svc/bsas/orderResult.json'),
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

        if ($response->json('status.processingCount') == 0) {
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiHoldingCertificateDocument);
            $traderOrder->update([
                'data' => $response->json('body.0'),
            ]);
        }
    }

    public function getBidCertificateDetails(TraderOrder $traderOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseDevURL('api/process/svc/bsas/bidXML.json'),
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
            logs()->debug('test', [$exception]);
            throw new TraderException(collect([
                'driver' => 'dmcc',
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
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseDevURL('api/process/svc/bsas/otcXML.json'),
            [
                'input' => [
                    'membershortname' => config('trader.providers.bursam.member_short_name'),
                    'ecertno' => $traderOrder->ecertNo,
                ],
            ]
        );
        dd($response->json());
    }

    public function sellingCommodityToOpenMarket(TraderOrder $traderOrder)
    {
        $traderOrder->update(['uuid' => Str::uuid()]);
        $financingOrder = $traderOrder->order;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseDevURL('api/process/svc/bsas/order.json'),
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
                    'valueDate' => $financingOrder->created_at->format('Ymd'),
                    'tenor' => '00090',
                    'otcCounterParty' => $financingOrder->customer_name,
                    'otcMurabaha' => '',
                    'otcMurabahaValue' => $financingOrder->selling_price->formatByDecimal(),
                    'eCertNo' => $traderOrder->ecertNo,
                ],
            ]
        );

        dd($response->json());
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
        match ((int) $traderOrder->last_history_action) {
            FinancingOrderHistory::GetTtiId => ProcessBursamOrderResult::dispatch($traderOrder),
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => ProcessBursamBidCertificate::dispatch($traderOrder),
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => ProcessBursamTransferOwnershipToLender::dispatch($traderOrder),
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => ProcessBursamSellingCommodityToOpenMarket::dispatch($traderOrder),
            default => null,
        };
    }
}
