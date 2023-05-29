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
            $token = Cache::remember('edaat_token', 604700, function () {
                $pendingRequest = $this->makePendingRequest()->asForm();

                $response = $pendingRequest->post('/auth', [
                    'grant_type' => 'password',
                    'username' => config('edaat.username'),
                    'password' => config('edaat.password'),
                ]);

                return $response->json('access_token');
            });

            $pendingRequest = $this->makePendingRequest()
                ->acceptJson()
                ->asJson()
                ->withToken($token);

            if ($pendingRequest === false) {
                $pendingRequest->withoutVerifying();
            }

            return $pendingRequest;
        });
    }

    public function makePendingRequest()
    {
        $baseUrl = config('edaat.base_url');
        $shouldVerifyTlsCerts = config('edaat.verify_tls_certs');

        $pendingRequest = Http::baseUrl($baseUrl);

        if ($shouldVerifyTlsCerts === false) {
            $pendingRequest->withoutVerifying();
        }

        return $pendingRequest;
    }
}
