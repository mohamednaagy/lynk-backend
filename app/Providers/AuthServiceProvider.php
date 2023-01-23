<?php

namespace App\Providers;

use App\Enums\Role;
use App\Enums\RouteArea;
use App\Models\Enquiry;
use App\Models\FinancingOrder;
use App\Policies\EnquiryPolicy;
use App\Policies\FinancingOrderPolicy;
use App\Policies\MediaPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
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
                'company_name' => tenant('unique_name'),
            ]);

            return rtrim($this->app['request']->input('redirect_url'), '/').'/'.$token.'?'.$query;
        });

        Gate::before(function ($user, $ability) {
            return match ($this->getArea()) {
                RouteArea::Admin => $user->hasRole([Role::Admin]) ? true : null,
                RouteArea::Lender => $user->hasRole([Role::LenderAdmin]) ? true : null,
                RouteArea::Trader => $user->hasRole([Role::TraderAdmin]) ? true : null,
                default => $user->hasRole([Role::Admin, Role::LenderAdmin, Role::TraderAdmin]) ? true : null
            };
        });
    }

    private function getArea()
    {
        $url = array_values(explode('/', $this->app->request->server->get('REQUEST_URI')));
        $area = isset($url[3]) ? $url[3] : null;
        if (in_array($area, RouteArea::getValues())) {
            return $area;
        }
    }
}
