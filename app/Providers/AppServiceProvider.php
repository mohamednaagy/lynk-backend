<?php

namespace App\Providers;

use App\Listeners\LogActivity;
use App\Services\LocalMarket\LoanCoverageStrategy\Contracts\LoanCoverageStrategy;
use App\Services\LocalMarket\LoanCoverageStrategy\Strategies\GreedyLoanCoverageStrategy;
use App\Services\LocalMarket\LoanCoverageStrategy\Strategies\OptimizedLoanCoverageStrategy;
use App\Services\Tokens\JWTService;
use App\Services\Tokens\TokenConfigResolver;
use App\Support\Traders\Events\ProcessNotification;
use App\Support\Traders\TraderManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\Base64UrlSafe;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
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
            $numeralInstance = new Numeral;

            $numeralInstance->setLanguageManager(new LanguageManager);

            return $numeralInstance;
        });

        $this->app->singleton(LoanCoverageStrategy::class, function () {
            return match (config('trader.providers.lynk.loan_coverage_strategy')) {
                'greedy' => new GreedyLoanCoverageStrategy,
                default => new OptimizedLoanCoverageStrategy, // Default to optimized
            };
        });

        $this->app->singleton(JWTService::class, function ($app) {
            $secret = config('jwt.secret');

            return new JWTService(
                secret: $secret,
                builder: new JWSBuilder(new AlgorithmManager([new HS256])),
                serializer: new CompactSerializer,
                key: new JWK([
                    'kty' => 'oct',
                    'k' => Base64UrlSafe::encodeUnpadded($secret),
                ]),
                configResolver: new TokenConfigResolver
            );
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
