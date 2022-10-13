<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\FormRequest;

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
        FormRequest::macro('safeInput', function (string $key = null, $default = null) {
            return $this->validator->safe()->only($key)[$key] ?? $default;
        });

    }
}
