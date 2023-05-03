<?php

namespace App\Support\Traders\Drivers;

use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\ProviderCredential;
use App\Models\TraderOrder;
use App\Support\Traders\Contracts\TraderInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BursamDriver implements TraderInterface
{
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

    public function initiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->activeTraderOrder()->exists()) {
            return $financingOrder->activeTraderOrder()->first();
        }

        return $financingOrder->traderOrders()->create([
            'uuid' => Str::uuid(),
            'provider' => 'bursam',
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function createTraderOrder(FinancingOrder $financingOrder)
    {
        $traderOrder = $this->initiateTraderOrder($financingOrder);

        Http::withHeaders([
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
    }

    public function fetchOrderResult(FinancingOrder $financingOrder)
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();

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

        $traderOrder->update([
            'data' => $response->json('body.0'),
        ]);
    }

    //Selling commodity to open market

public function sellingCommodityToOpenMarket(TraderOrder $traderOrder)
{
    $traderOrder->update(['uuid' => Str::uuid()]);

    Http::withHeaders([
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
                'bidValue' => $traderOrder->order->amount->formatByDecimal(),
                'valueDate' => $traderOrder->order->created_at->format('Ymd'),
                'tenor' => '00090',
                'otcCounterParty' => $traderOrder->order->customer_name,
                'otcMurabaha' => '',
                'otcMurabahaValue' => $traderOrder->order->selling_price->formatByDecimal(),
                'eCertNo' => $traderOrder->ecertNo,
            ],
        ]
    );
}

    public function acceptAgreement()
    {
    }

    public function getTti(FinancingOrder $financingOrder)
    {
    }

    public function fetchNotifications(string $type)
    {
    }

    public function cancelOrder(FinancingOrder $financingOrder): mixed
    {
        return '';
    }
}
