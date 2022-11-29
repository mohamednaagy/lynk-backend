<?php

namespace App\Providers;

use App\Support\Traders\TraderManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->app->singleton('trader.store', function ($app) {
            return $app->make('dmcc')->driver();
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

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();
        });

        DB::macro('multipleTransaction', function (\Closure $transaction) {
            return DB::transaction(function () use ($transaction) {
                return DB::connection(Config::get('wallet.database.connection'))->transaction(
                    function () use ($transaction) {
                        return $transaction();
                    });
            });
        });
    }
}
