<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    private array $allowedEntryTypes = [
        EntryType::REQUEST,
        EntryType::QUERY,
    ];

    public function register(): void
    {
        // Always register Telescope
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            // Always include reportable exceptions
            if ($entry->isReportableException()) {
                return true;
            }

            // Allow only specific entry types
            return in_array($entry->type, $this->allowedEntryTypes, true);
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
        Gate::define('viewTelescope', fn ($user) => false);
    }
}
