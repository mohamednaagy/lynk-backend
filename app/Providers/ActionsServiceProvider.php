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
        $contractPrefix = 'App\\Actions\\Contracts';
        $actionPrefix = 'App\\Actions';
        $actionSuffix = 'Action';

        foreach (get_declared_interfaces() as $contract) {
            if (str_starts_with($contract, $contractPrefix)) {
                $action = $actionPrefix.explode('Contracts', $contract)[1].$actionSuffix;

                $this->app->bind($contract, $this->customBindings[$contract] ?? $action);
            }
        }
    }
}
