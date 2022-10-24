<?php

namespace App\Providers;

use App\Services\SmsDriverManger;
use App\Support\Sms\Sms;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('Sms', function ($app) {
            // return new Sms();
            return new SmsDriverManger($app);
        });
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
