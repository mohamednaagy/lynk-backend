<?php

namespace App\Support\Traders\Drivers;

use App\Models\FinancingOrder;
use App\Models\ProviderCredential;
use App\Models\TraderOrder;
use App\Support\Traders\Contracts\TraderInterface;
use Illuminate\Support\Facades\Http;

class BursamDriver implements TraderInterface
{
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
            'client_id' => config('trader.providers.bursam.client_id'),
            'client_secret' => config('trader.providers.bursam.client_secret'),
        ]);

        // store token with provider name
        ProviderCredential::updateOrCreate(
            ['provider_name' => 'bursam'],
            ['access_token' => $response->json('access_token')],
        );
    }

    public function createTraderOrder(FinancingOrder $financingOrder, string $providerName): string
    {
        $traderOrder = TraderOrder::create([
            'financing_order_id' => $financingOrder,
            'provider_name' => $providerName,
        ]);

        return $traderOrder->uuid;
    }

    public function createOrder(FinancingOrder $financingOrder)
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();

        $accessToken = ProviderCredential::where('provider_name', 'bursam')->value('access_token');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'memberShortName' => config('trader.providers.bursam.client_id'),
            'uuid' => $traderOrder->uuid])
            ->post(
                $this->baseURL('api/process/svc/order'),
                [
                    'serialNumber' => '1',
                    'bidOption' => 'Y',
                    'otcOption' => 'N',
                    'stbOption' => 'N',
                    'productCode' => 'CPO-MSIA-09',
                    'purchaseType' => 'P',
                    'clientName' => $financingOrder->customer_name,
                    'currency' => 'SAR',
                    'bidValue' => '87532497.52',
                    'valueDate' => '20230411',
                    'tenor' => '00035',
                    'otcCounterParty' => 'ABC',
                    'otcMurabaha' => '',
                    'otcMurabahaValue' => '88532497.72',
                    'eCertNo' => '',
                ]
            );

        return $response;
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
