<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
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
            int   $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_OK) {
            return response()->json([
                'data' => $data
            ], $statusCode);
        });

        Response::macro('errorResponse', function (
            string $message = 'something went wrong',
            int    $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
            int    $code = null
        ) {
            $response = [
                'message' => $message,
            ];

            if (!is_null($code)) {
                $response = array_merge($response, ['code' => $code]);
            }

            return response()->json($response, $statusCode);
        });
    }
}
