<?php

namespace App\Providers;

use App\Support\Wallets\Contracts\TransactionUtilInterface;
use App\Support\Wallets\Transactions\TransactionUtil;
use Illuminate\Support\ServiceProvider;

class TransactionUtilServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            TransactionUtilInterface::class,
            TransactionUtil::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
