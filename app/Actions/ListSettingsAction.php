<?php

namespace App\Actions;

use App\Enums\Area;
use Modules\Otpify\Facades\Otpify;
use App\Actions\Contracts\ListSettings;
use App\Actions\Contracts\GetSettingsClassInstance;

class ListSettingsAction implements ListSettings
{
    /**
     * UpdateSettingsAction constructor.
     * @param GetSettingsClassInstance $getSettingsClassInstance
     */
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    )
    {
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        $areas = Area::getValues();
        $settings = [];

        foreach ($areas as $area) {
            $settings[$area] = $this->getSettingsClassInstance->handle($area)->toArray();
        }

        $settings['otp_drivers'] = Otpify::getOtpifyDrivers();
        $settings['areas'] = $areas;

        return $settings;
    }
}
