<?php

namespace App\Providers;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Throwable;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     *
     * @throws Throwable
     */
    public function boot(): void
    {
        DB::macro('multipleTransaction', function (Closure $transaction) {
            return DB::transaction(function () use ($transaction) {
                return DB::connection(Config::get('wallet.database.connection'))->transaction(
                    function () use ($transaction) {
                        return $transaction();
                    });
            });
        });
    }
}
