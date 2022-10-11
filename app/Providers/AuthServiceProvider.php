<?php

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            // TODO: need to map the role to the request path.
            // For example: /api/v1/admin => Role::Admin
            // For example: /api/v1/lender => Role::LenderAdmin

            /** @var \App\Models\User $user */
            return $user->hasRole([Role::Admin, Role::LenderAdmin]) ? true : null;
        });
    }
}
