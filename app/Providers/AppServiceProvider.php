<?php

namespace App\Providers;

use App\Listeners\LogActivity;
use App\Support\Traders\Events\ProcessNotification;
use App\Support\Traders\TraderManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Stillat\Numeral\Languages\LanguageManager;
use Stillat\Numeral\Numeral;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
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

        $this->app->singleton('numeral', function () {
            $numeralInstance = new Numeral();

            $numeralInstance->setLanguageManager(new LanguageManager);

            return $numeralInstance;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https', false)) {
            URL::forceScheme('https');
        }

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
            ?int $code = null
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

        Event::listen(
            ProcessNotification::class,
            [LogActivity::class, 'handle']
        );

        Config::set('cors.allowed_origins', app()->isProduction()
            ? array_values(Config::get('app.frontend_url'))
            : ['*']);
    }
}
