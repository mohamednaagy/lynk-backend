<?php

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim($this->app['request']->input('redirect_url'), '/').'/'.$token;
        });

        Gate::before(function ($user, $ability) {
            // TODO: need to map the role to the request path.
            // For example: /api/v1/admin => Role::Admin
            // For example: /api/v1/lender => Role::LenderAdmin

            /** @var \App\Models\User $user */
            return $user->hasRole([Role::Admin, Role::LenderAdmin]) ? true : null;
        });
    }
}
