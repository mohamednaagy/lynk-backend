<?php

namespace App\Providers;

use App\Support\OTP\AbsherDriver;
use App\Support\OTP\Core\Otp;
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

        $this->app->bind('otp', function () {
            return new Otp(new AbsherDriver);
        });
    }
}
