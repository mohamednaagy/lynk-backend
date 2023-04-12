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
        // initialize access token to empty string
        $this->accessToken = '';
    }

    public function baseURL($path)
    {
        return 'https://bsasapi.bursamalaysia.com';
    }

    /**
     * @return string
     */
    private function getAuth()
    {
        $response = Http::get($this->baseURL('/svc/auth/authorize'), [
            'client_id' => config('trader.providers.bursam.client_id'),
            'response_type' => config('trader.providers.bursam.response_type'),
            'redirect_uri' => 'https://'.config('trader.providers.bursam.redirect_uri').'/webservice/BsasRcv',
        ]);

        // parse the response and extract the authorization code
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
        $this->accessToken = $this->parseAccessToken($response->body());
    }

    // helper function to parse access token from response
    private function parseAccessToken($response)
    {
        // TODO: implement parsing of access token from response
        return '';
    }

    // 3. call new order API with access token
    public function placeOrder($order)
    {
        // check if access token is available
        if ($this->accessToken === '') {
            $this->getAccessToken();
        }

        // call new order API with access token
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'text/xml',
        ])->post($this->baseURL('/svc/order'), $order);

        // parse the response and return the result
        return $this->parseOrderResponse($response->body());
    }

    // helper function to parse order response
    private function parseOrderResponse($response)
    {
        // TODO: implement parsing of order response
        return '';
    }
}
