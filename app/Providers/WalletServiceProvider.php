<?php

namespace App\Providers;

use App\Support\Wallets\Contracts\WalletService;
use App\Support\Wallets\WalletServiceAction;
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
            WalletService::class,
            function () {
                return new WalletServiceAction();
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
