<?php

namespace App\Providers;

use App\Enums\Role;
use App\Enums\RouteArea;
use App\Models\Enquiry;
use App\Models\FinancingOrder;
use App\Models\PersonalAccessToken;
use App\Policies\EnquiryPolicy;
use App\Policies\FinancingOrderPolicy;
use App\Policies\MediaPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Media::class => MediaPolicy::class,
        FinancingOrder::class => FinancingOrderPolicy::class,
        Enquiry::class => EnquiryPolicy::class,
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
            $query = http_build_query([
                'email' => $user->email,
                'company_unique_name' => tenant('unique_name'),
            ]);

            return rtrim($this->app['request']->input('redirect_url'), '/').'/'.$token.'?'.$query;
        });

        Gate::before(function ($user, $ability) {
            return match ($this->getAreaFromRequestPath()) {
                RouteArea::Admin => $user->hasRole([Role::Admin]) ?: null,
                RouteArea::Lender => $user->hasRole([Role::LenderAdmin, Role::LenderApiUser]) ?: null,
                RouteArea::Trader => $user->hasRole([Role::TraderAdmin]) ?: null,
                RouteArea::Supplier => $user->hasRole([Role::SupplierAdmin]) ?: null,
                default => null
            };
        });

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    private function getAreaFromRequestPath()
    {
        $path = explode('/', $this->app->request->path());
        $area = isset($path[2]) ? $path[2] : null;
        if (in_array($area, RouteArea::getValues())) {
            return $area;
        }
    }
}
