<?php

namespace App\Providers;

use App\Support\Traders\Drivers\DmccDriver;
use App\Support\Traders\TraderManager;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->singleton('trader', function ($app) {
            return new TraderManager($app);
        });

        $trader = app('trader');
        $trader->extend('dmcc', function ($app) {
            return new DmccDriver($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Response::macro('successResponse', function (
            array $data = [],
            int $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_OK
        ) {
            return response()->json([
                'data' => $data,
            ], $statusCode);
        });

        Response::macro('errorResponse', function (
            string $message = 'something went wrong',
            int $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
            int $code = null
        ) {
            $response = [
                'message' => $message,
            ];

            if (! is_null($code)) {
                $response = array_merge($response, ['code' => $code]);
            }

            return response()->json($response, $statusCode);
        });
    }
}
