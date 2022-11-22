<?php

namespace App\Providers;

use App\Support\Wallets\Contracts\WalletServiceInterface;
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
            function () {
                return new WalletService();
            }
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
