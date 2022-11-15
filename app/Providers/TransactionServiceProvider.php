<?php

namespace App\Providers;

use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterFace;
use App\Support\Generator\ReferenceNumber\ReferenceNumberGenerator;
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
        $this->app->singleton(TransactionServiceInterface::class, function () {
            return new TransactionService();
        });

        $this->app->singleton(ReferenceNumberGeneratorInterFace::class, config('wallet.reference_number.generator') ?? ReferenceNumberGenerator::class);
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
