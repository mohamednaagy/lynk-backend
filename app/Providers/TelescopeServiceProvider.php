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
        EntryType::EXCEPTION,
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

            // Handle queries separately - only log slow ones
            if ($entry->type === EntryType::QUERY) {
                // Skip if query is on telescope's own connection
                if (isset($entry->content['connection']) &&
                    $entry->content['connection'] === config('telescope.storage.database.connection')) {
                    return false;
                }

                // Only log slow queries
                return $entry->content['slow'] ?? false;
            }

            // Allow other specific entry types
            return in_array($entry->type, $this->allowedEntryTypes, true);
        });
    }

    protected function hideSensitiveRequestDetails(): void
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

    protected function gate(): void
    {
        Gate::define('viewTelescope', fn ($user) => false);
    }
}
