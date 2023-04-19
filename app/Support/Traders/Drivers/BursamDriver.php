<?php

namespace App\Support\Traders\Drivers;

use App\Models\ProviderCredential;
use App\Support\Traders\Contracts\TraderInterface;
use Illuminate\Support\Facades\Http;

class BursamDriver extends DmccDriver implements TraderInterface
{
    public function baseURL($path)
    {
        return 'https://bsasapi.bursamalaysia.com/'.$path;
    }

    public function baseDevURL($path)
    {
        return 'http://traderdcthh-erfmbxcc1323421.uselynk.com/'.$path;
    }

    /**
     * @return string
     */
    public function updateProviderCredential(): void
    {
        $response = Http::get($this->baseDevURL('api/process/svc/auth/token?'),
            'grant_type='.config('trader.providers.bursam.grant_type').
            '&client_id='.config('trader.providers.bursam.client_id').
            '&client_secret='.config('trader.providers.bursam.client_secret')
        );

        dd($response->json());
        // store token with provider name
        ProviderCredential::updateOrCreate(
            ['provider_name' => 'bursam'],
            ['access_token' => $response->json('access_token')],
        );
    }
}
