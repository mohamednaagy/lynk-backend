<?php

namespace App\Providers;

use App\Support\Transactions\Service\TransactionService;
use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(TransactionServiceInterface::class, function () {
            return new TransactionService;
        });
    }
}
