<?php

namespace App\Providers;

use App\Services\Tokens\Config\TokenConfigResolver;
use App\Services\Tokens\Contracts\TokenGeneratorInterface;
use App\Services\Tokens\TokenService;
use Illuminate\Support\ServiceProvider;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\Base64UrlSafe;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;

class TokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerAlgorithmManager();
        $this->registerSigningKey();
        $this->registerJoseComponents();
        $this->registerTokenService();
    }

    protected function registerAlgorithmManager(): void
    {
        $this->app->singleton('jwt.signature_algorithm_manager', fn () => new AlgorithmManager([new HS256]));
    }

    protected function registerSigningKey(): void
    {
        $this->app->singleton('jwt.signing_key', function () {
            return new JWK([
                'kty' => 'oct',
                'k' => Base64UrlSafe::encodeUnpadded(config('jwt.secret')),
            ]);
        });
    }

    protected function registerJoseComponents(): void
    {
        $this->app->singleton(JWSBuilder::class, fn ($app) => new JWSBuilder($app['jwt.signature_algorithm_manager']));
        $this->app->singleton(JWSVerifier::class, fn ($app) => new JWSVerifier($app['jwt.signature_algorithm_manager']));
        $this->app->singleton(TokenConfigResolver::class, fn ($app) => new TokenConfigResolver($app['request']));
    }

    protected function registerTokenService(): void
    {
        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService(
                jwsBuilder: $app[JWSBuilder::class],
                signingKey: $app['jwt.signing_key'],
                configResolver: $app[TokenConfigResolver::class],
            );
        });

        $this->app->alias(TokenService::class, TokenGeneratorInterface::class);
    }

    public function provides(): array
    {
        return [
            TokenService::class,
            TokenGeneratorInterface::class,
            JWSBuilder::class,
            JWSVerifier::class,
            TokenConfigResolver::class,
            'jwt.signature_algorithm_manager',
            'jwt.signing_key',
        ];
    }
}
