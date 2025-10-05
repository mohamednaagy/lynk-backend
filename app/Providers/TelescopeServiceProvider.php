<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register()
    {
        // Always register Telescope
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->hideSensitiveRequestDetails();

        Telescope::filter(function ($entry) {
            // Always log requests
            if ($entry->type === EntryType::REQUEST) {
                return true;
            }

            // Log queries (so you can see them under each request)
            if ($entry->type === EntryType::QUERY) {
                return true;
            }

            return false; // ignore everything else
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
