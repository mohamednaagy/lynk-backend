<?php

namespace App\Providers;

use App\Enums\Area;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use App\Settings\Classes\GeneralSettings;
use App\Settings\Support\SettingsRegistry;
use App\Settings\Classes\Areas\CustomerSettings;
use App\Settings\Services\GeneralSettingsService;
use App\Settings\Classes\Areas\SuperAdminSettings;
use App\Settings\Services\CustomerSettingsService;
use App\Settings\Services\SuperAdminSettingsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Response::macro('successResponse', function (
            array $data = [],
            int   $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_OK) {
            return response()->json([
                'data' => $data
            ], $statusCode);
        });

        Response::macro('errorResponse', function (
            string $message = 'something went wrong',
            int    $code = 400,
            int    $statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST
        ) {
            return response()->json([
                'message' => $message,
                'code' => $code
            ], $statusCode);
        });

        // register all setting classes into settingRegister Class
        $this->app->make(SettingsRegistry::class)
            ->register(Area::SuperAdmin, new SuperAdminSettings(), new SuperAdminSettingsService());
        $this->app->make(SettingsRegistry::class)
            ->register(Area::Customer, new CustomerSettings(), new CustomerSettingsService());
        $this->app->make(SettingsRegistry::class)
            ->register('General', new GeneralSettings(), new GeneralSettingsService());
    }
}
