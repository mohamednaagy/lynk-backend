<?php

namespace App\Support\OTP;

use App\Support\OTP\Core\OtpAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class AbsherDriver implements OtpAdapter
{
    protected $api_key;

    public function __construct()
    {
        $this->api_key = Config::get('otp.api_key');
    }

    /**
     * Summary of send
     *
     * @param  mixed  $nationalId
     * @return mixed
     */
    public function send(string $nationalId): mixed
    {
        $url = 'http//example/integration/send';
        $data = [
            'apiKey' => $this->api_key,
            'personId' => $nationalId,
        ];

        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->post($url, $data);

        return $response->json();
    }

    /**
     * @param  string  $tcn
     * @param  string  $otp
     * @return mixed
     */
    public function check(string $tcn, string $otp): mixed
    {
        $url = 'http//example/integration/check';

        $data = [
            'apiKey' => $this->api_key,
            'tcn' => $tcn,
            'otp' => $otp,
        ];

        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->post($url, $data);

        return $response->json();
    }
}
