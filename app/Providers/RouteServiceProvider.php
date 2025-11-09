<?php

namespace App\Providers;

use App\Enums\CompanyType;
use App\Models\CompanyLenderClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/auth.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/lender.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/admin.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/visitor.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/client.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/edaat.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/test.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/supplier.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/supplier.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        Route::bind('lender', function ($id) {
            return Lender::where('id', $id)
                ->where('type', CompanyType::Lender)
                ->firstOrFail();
        });

        Route::bind('client', function ($id, $route) {
            // Get the lender from the route to scope the client lookup
            $lender = $route->parameter('lender');
            if ($lender) {
                return CompanyLenderClient::withoutTrashed()
                    ->where('id', $id)
                    ->where('company_id', $lender->id)
                    ->firstOrFail();
            }

            return CompanyLenderClient::withoutTrashed()->findOrFail($id);
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            if (config('app.enable_rate_limiter')) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            }

            return Limit::none();
        });
    }
}
