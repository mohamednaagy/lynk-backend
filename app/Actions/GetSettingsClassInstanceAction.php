<?php

namespace App\Actions;

use App\Enums\Area;
use Spatie\LaravelSettings\Settings;
use App\Settings\Classes\GeneralSettings;
use function Symfony\Component\String\match;
use App\Settings\Classes\Areas\CustomerSettings;
use App\Settings\Classes\Areas\SuperAdminSettings;
use App\Actions\Contracts\GetSettingsClassInstance;

class GetSettingsClassInstanceAction implements GetSettingsClassInstance
{
    /**
     * @param string $key
     * @return Settings
     */
    public function handle(string $key): Settings
    {
        return match ($key) {
                'General' => app(GeneralSettings::class),
                Area::SuperAdmin => app(SuperAdminSettings::class),
                Area::Customer => app(CustomerSettings::class),
            };
    }
}
