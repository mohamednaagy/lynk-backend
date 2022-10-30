<?php

namespace App\Providers;

use App\Support\MobileVerification\MobileVerifyManager;
use Illuminate\Support\ServiceProvider;

class MobileVerifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('mobile-verify', function ($app) {
            return new MobileVerifyManager($app);
        });
        $this->app->singleton('mobile-verify.store', function ($app) {
            return $app->make('mobile-verify')->driver();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
