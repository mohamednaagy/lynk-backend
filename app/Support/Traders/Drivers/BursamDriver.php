<?php

namespace App\Support\Traders\Drivers;

use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\ProviderCredential;
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
                    'productCode' => 'CPO-MSIA-09', // get it from request
                    'purchaseType' => 'P',
                    'clientName' => $financingOrder->customer_name,
                    'currency' => 'SAR',
                    'bidValue' => '87532497.52',   // get it from request // $financingOrder->amount
                    'valueDate' => '20230411',   // get it from request
                    'tenor' => '00035',
                    'otcCounterParty' => 'ABC',
                    'otcMurabaha' => '',
                    'otcMurabahaValue' => '88532497.72',  // get it from request // $financingOrder->selling_price
                    'eCertNo' => '',
                ],
            ]
        );

        $response->json();
    }

    public function fetchOrderResult(FinancingOrder $financingOrder)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            $this->baseDevURL('api/process/svc/bsas/orderResult.json'),
            [
                'header' => [
                    'memberShortName' => config('trader.providers.bursam.member_short_name'),
                    'uuid' => $financingOrder->activeTraderOrder()->first()?->uuid,
                ],
                'request' => [
                    'serialNumber' => '1',
                    'forceYN' => 'Y',
                    'maxWaitTime' => '10',
                    'waitAllDoneYN' => 'Y',
                ],
            ]
        );

        dd($response->json()); // continue implement that
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
