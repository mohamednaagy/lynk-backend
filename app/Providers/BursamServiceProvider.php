<?php

namespace App\Providers;

use App\Exceptions\BURSAM\BursamAccessTokenException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class BursamServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootBursam();
    }

    private function bootBursam()
    {
        Http::macro('bursam', function () {
            $baseUrl = config('trader.providers.bursam.base_url');

            $token = Cache::get('bursam_access_token');

            if (! $token) {
                try {
                    $data = [
                        'grant_type' => config('trader.providers.bursam.grant_type'),
                        'client_id' => config('trader.providers.bursam.member_short_name'),
                        'client_secret' => config('trader.providers.bursam.client_secret_key'),
                    ];

                    $response = Http::asForm()
                        ->withOptions([
                            'verify' => config('trader.providers.bursam.verify_tls'),
                            'allow_redirects' => [
                                'strict' => true,
                            ],
                            'connect_timeout' => 0,
                            'timeout' => 0,
                        ])
                        ->baseUrl($baseUrl)
                        ->post('api/process/svc/auth/token', $data);

                    $token = $response->json('access_token');

                    Log::channel('bursam')->info('Malaysia Bursa Get token request: ...'.json_encode([
                        'url' => $baseUrl.'api/process/svc/auth/token',
                        'request' => $data,
                        'response' => $response->json(),
                        'statusCode' => $response->getStatusCode(),
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                    if (! $token) {
                        throw new BursamAccessTokenException('Failed to get access token from Bursam Response');
                    }

                    Cache::put('bursam_access_token', $token, $response->json('expires_in') - 1000);
                } catch (BursamAccessTokenException $e) {
                    // Handle the exception here
                    Log::error('Error While Trying To Get Token From BURSAM', ['exception' => $e->getMessage()]);
                    throw $e;
                }
            }

            return Http::acceptJson()
                ->asJson()
                ->withOptions([
                    'verify' => config('trader.providers.bursam.verify_tls'),
                    'allow_redirects' => [
                        'strict' => true,
                    ],
                    'connect_timeout' => 0,
                    'timeout' => 0,
                ])
                ->withToken($token)
                ->baseUrl($baseUrl);
        });
    }
}
