<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ActionsServiceProvider extends ServiceProvider
{
    public array $customBindings = [];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $prefix = 'App\\Actions';
        $suffix = 'Action';
        foreach (get_declared_classes() as $class) {
            if (str_starts_with($class, $prefix)) {
                $abstract = $prefix.'\\Contracts'.substr(
                    $class,
                    strlen($prefix),
                    strlen($class) - strlen($prefix.$suffix)
                );

                $this->app->bind($abstract, $this->customBindings[$abstract] ?? $class);
            }
        }
    }
}
