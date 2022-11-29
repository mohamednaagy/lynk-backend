<?php

namespace App\Providers;

use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterface;
use App\Support\Generator\ReferenceNumber\ReferenceNumberGenerator;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Support\Wallets\Contracts\WalletServiceInterface;
use App\Support\Wallets\TransactionService;
use App\Support\Wallets\WalletService;
use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            WalletServiceInterface::class,
            WalletService::class
        );

        $this->app->singleton(
            TransactionServiceInterface::class,
            TransactionService::class
        );

        $this->app->singleton(
            ReferenceNumberGeneratorInterface::class,
            config('wallet.reference_number.generator') ?? ReferenceNumberGenerator::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
