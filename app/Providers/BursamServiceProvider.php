<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            $token = Cache::remember('bursam_access_token', 79200, function () use ($baseUrl) {
                $response = Http::asForm()
                    ->withOptions([
                        'verify' => config('trader.providers.bursam.verify_tls'),
                    ])
                    ->post($baseUrl.'/api/process/svc/auth/token', [
                        'grant_type' => config('trader.providers.bursam.grant_type'),
                        'client_id' => config('trader.providers.bursam.member_short_name'),
                        'client_secret' => config('trader.providers.bursam.client_secret_key'),
                    ]);

                return $response->json('access_token');
            });

            return Http::acceptJson()
                ->asJson()
                ->withOptions([
                    'verify' => config('trader.providers.bursam.verify_tls'),
                ])
                ->withToken($token)
                ->baseUrl($baseUrl);
        });
    }
}
