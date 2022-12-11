<?php

namespace App\Providers;

use App\Listeners\LogActivity;
use App\Support\Sms\Events\SmsSent;
use App\Support\Sms\SmsManager;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton('sms.manager', function ($app) {
            return new SmsManager($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(
            SmsSent::class,
            [LogActivity::class, 'handle']
        );
    }
}
