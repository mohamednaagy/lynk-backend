<?php

namespace App\Actions;

use App\Actions\Contracts\UpdateSettings;
use App\Settings\Support\SettingsRegistry;

class UpdateSettingsAction implements UpdateSettings
{
    /**
     * @param array $data
     * @return void
     */
    public function handle(array $data): void
    {
        $settingService = SettingsRegistry::getSettingServiceByKey($data['area']);
        $settingService->update($data);
    }
}
