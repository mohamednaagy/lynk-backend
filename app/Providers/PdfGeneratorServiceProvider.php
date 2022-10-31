<?php

namespace App\Providers;

use App\Support\PdfGenerator\PdfGenerator;
use App\Support\PdfGenerator\PdfGeneratorManager;
use Illuminate\Support\ServiceProvider;

class PdfGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PdfGenerator::class, function () {
            return new PdfGeneratorManager($this->app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
