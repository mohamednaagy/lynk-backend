<?php

namespace App\Providers;

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
                $response = Http::asForm()
                    ->withOptions([
                        'verify' => config('trader.providers.bursam.verify_tls'),
                        'connect_timeout' => 0,
                        'timeout' => 0,
                    ])
                    ->baseUrl($baseUrl)
                    ->post('api/process/svc/auth/token', [
                        'grant_type' => config('trader.providers.bursam.grant_type'),
                        'client_id' => config('trader.providers.bursam.member_short_name'),
                        'client_secret' => config('trader.providers.bursam.client_secret_key'),
                    ]);

                $token = $response->json('access_token');

                if (! $token) {
                    Log::error('Failed to get access token from Bursam', $response->json());
                    throw new \Exception('Failed to get access token from Bursam');
                }

                Cache::put('bursam_access_token', $token, $response->json('expires_in') - 1000);
            }

            return Http::acceptJson()
                ->asJson()
                ->withOptions([
                    'verify' => config('trader.providers.bursam.verify_tls'),
                    'connect_timeout' => 0,
                    'timeout' => 0,
                ])
                ->withToken($token)
                ->baseUrl($baseUrl);
        });
    }
}
