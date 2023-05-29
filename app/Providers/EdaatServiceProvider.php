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
                $pendingRequest = EdaatServiceProvider::makePendingRequest()->asForm();

                $response = $pendingRequest->post('/auth', [
                    'grant_type' => 'password',
                    'username' => config('edaat.username'),
                    'password' => config('edaat.password'),
                ]);

                return $response->json('access_token');
            });

            return EdaatServiceProvider::makePendingRequest()
                ->acceptJson()
                ->asJson()
                ->withToken($token);
        });
    }

    public static function makePendingRequest()
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
