<?php

namespace App\Actions;

use App\Enums\Area;
use Modules\Otpify\Facades\Otpify;
use App\Actions\Contracts\ListSettings;
use App\Settings\Support\SettingsRegistry;

class ListSettingsAction implements ListSettings
{
    /**
     * @return array
     */
    public function handle(): array
    {
        $areas = Area::getValues();
        $settings = [];

        foreach ($areas as $area) {
            $settings[$area] = SettingsRegistry::getSettingInstanceByKey($area)->toArray();
        }

        $settings['otp_drivers'] = Otpify::getOptifyDrivers();
        $settings['areas'] = $areas;

        return $settings;
    }
}
