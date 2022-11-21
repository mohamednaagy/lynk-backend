<?php

namespace App\Providers;

use App\Mixin\SignedExternalRouteMixin;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
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
        URL::mixin(new SignedExternalRouteMixin);
    }
}
