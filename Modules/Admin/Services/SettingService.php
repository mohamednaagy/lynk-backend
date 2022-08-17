<?php

namespace Modules\Admin\Services;

use Modules\Otpify\Facades\Otpify;
use Modules\Permission\Enums\Area;
use Modules\Settings\Support\SettingsRegistry;

class SettingService
{
    /**
     * @return array
     */
    public function listSettings(): array
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

    /**
     * @param array $data
     * @return void
     */
    public function update(array $data): void
    {
        $settingService = SettingsRegistry::getSettingServiceByKey($data['area']);
        $settingService->update($data);
    }
}
