<?php

namespace App\Providers;

use App\Enums\CompanyType;
use App\Models\Company;
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
                ->group(base_path('routes/api/trader.php'));

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
                ->group(base_path('routes/api/trader.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/supplier.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        Route::bind('trader', function ($id) {
            return Company::query()->where('id', $id)
                ->where('type', CompanyType::Trader)
                ->firstOrFail();
        });

        Route::bind('lender', function ($id) {
            return Company::where('id', $id)
                ->where('type', CompanyType::Lender)
                ->firstOrFail();
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
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
