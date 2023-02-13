<?php

namespace App\Providers;

use App\Support\StructureScouts\ActionsStructureScout;
use Illuminate\Support\ServiceProvider;
use Spatie\StructureDiscoverer\Support\StructureScoutManager;

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
        StructureScoutManager::add(ActionsStructureScout::class);

        $contractPrefix = 'App\\Actions\\Contracts';
        $actionPrefix = 'App\\Actions';
        $actionSuffix = 'Action';

        $contracts = ActionsStructureScout::create()->get();

        foreach ($contracts as $contract) {
            if (str_starts_with($contract, $contractPrefix)) {
                $action = $actionPrefix.explode('Contracts', $contract)[1].$actionSuffix;

                $this->app->bind($contract, $this->customBindings[$contract] ?? $action);
            }
        }
    }
}
