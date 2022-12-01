<?php

namespace App\Providers;

use App\Mixin\SignedExternalRouteMixin;
use BenSampo\Enum\Enum;
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
     *
     * @throws \ReflectionException
     */
    public function boot()
    {
        URL::mixin(new SignedExternalRouteMixin);

        Enum::macro('joinDescription', function (array $values, string $separator = ',') {
            return implode($separator, array_map(fn ($value) => (new static($value))->description, $values));
        });
    }
}
