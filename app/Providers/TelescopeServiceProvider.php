<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    private array $allowedEntryTypes = [
        EntryType::REQUEST,
        EntryType::QUERY,
    ];

    public function register()
    {
        // Always register Telescope
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->hideSensitiveRequestDetails();

        Telescope::filter(function ($entry) {
            return $this->app->isLocal() || in_array($entry->type, $this->allowedEntryTypes, true);
        });
    }

    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    protected function gate()
    {
        Gate::define('viewTelescope', function ($user) {
            return false;
        });
    }
}
