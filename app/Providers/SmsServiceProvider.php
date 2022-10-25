<?php

namespace App\Providers;

use App\Services\SmsDriverManger;
use App\Support\Sms\Sms;
use App\Support\Sms\SmsManger;
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
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->app->singleton('Sms', function ($app) {
            // return new Sms();
            return new SmsManger($app);
            // return new SmsDriverManger($app);
        });
    }
}
