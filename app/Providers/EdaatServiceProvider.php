<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class EdaatServiceProvider extends ServiceProvider
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
        $this->bootEdaat();
    }

    private function bootEdaat()
    {
        Http::macro('edaat', function () {
            $baseUrl = config('edaat.base_url');
            $token = Cache::remember('edaat_token', 604700, function () {
                $response = Http::asForm()->post(config('edaat.base_url').'/auth', [
                    'grant_type' => 'password',
                    'username' => config('edaat.username'),
                    'password' => config('edaat.password'),
                ]);

                return $response->json('access_token');
            });

            $http = Http::acceptJson()
                ->asJson()
                ->withToken($token)
                ->baseUrl($baseUrl);

            if (config('edaat.verify_tls_certs') === false) {
                $http->withoutVerifying();
            }

            return $http;
        });
    }
}
