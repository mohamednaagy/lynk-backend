<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Settings\Classes\Areas\CustomerSettings;
use App\Settings\Classes\Areas\LenderSettings;
use App\Settings\Classes\Areas\SuperAdminSettings;
use App\Settings\Classes\Areas\TraderSettings;
use App\Settings\Classes\GeneralSettings;
use Spatie\LaravelSettings\Settings;

class GetSettingsClassInstanceAction implements GetSettingsClassInstance
{
    /**
     * @param  string  $key
     * @return Settings
     */
    public function handle(string $key): Settings
    {
        return match ($key) {
            'General' => app(GeneralSettings::class),
            Area::SuperAdmin => app(SuperAdminSettings::class),
            Area::Customer => app(CustomerSettings::class),
            Area::Lender => app(LenderSettings::class),
            Area::Trader => app(TraderSettings::class),
        };
    }
}
