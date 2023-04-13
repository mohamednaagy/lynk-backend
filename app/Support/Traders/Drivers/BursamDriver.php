<?php

namespace App\Support\Traders\Drivers;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Support\Facades\Http;

class BursamDriver extends DmccDriver implements TraderInterface
{
    use TraderHelperTrait;

    private string $accessToken;

    public function __construct()
    {
        $this->accessToken = '';
    }

    public function baseURL($path)
    {
        return 'https://bsasapi.bursamalaysia.com/'.$path;
    }

    public function baseDevURL($path)
    {
        return 'https://bsasdevapi.bursamalaysia.com/'.$path;
    }

    /**
     * @return string
     */
    private function getAuth()
    {
        $response = Http::get($this->baseDevURL('api/process/svc/auth/token'), [
            'grant_type' => config('trader.providers.bursam.grant_type'),
            'client_id' => config('trader.providers.bursam.client_id'),
            'client_secret_code' => config('trader.providers.bursam.client_secret_code'),
        ]);

        $authCode = $this->parseAuthResponse($response->body());

        return $authCode;
    }

    // helper function to parse authorization code from response
    private function parseAuthResponse($response)
    {
        // TODO: implement parsing of authorization code from response
        return '';
    }

    // 2. request access token from BSAS
    private function getAccessToken()
    {
        $response = Http::post($this->baseURL('/svc/auth/token'), [
            'grant_type' => 'authorization_code',
            'client_id' => config('trader.providers.bursam.client_id'),
            'client_secret' => config('trader.providers.bursam.client_secret'),
            'code' => $this->getAuth(),
        ]);

        // parse the response and extract the access token
        $this->accessToken = $response->body();

    }

    // 3. call new order API with access token
    public function placeOrder($order)
    {
        // check if access token is available
        if ($this->accessToken === '') {
            // token provider
        }

        // call new order API with access token
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'text/xml',
        ])->post($this->baseURL('svc/order'), $order);

        // parse the response and return the result
        return $this->getTokenOrderResponse($response->body());
    }

    // helper function to parse order response
    private function getTokenOrderResponse($response)
    {
        // TODO: implement parsing of order response
        return '';
    }
}
