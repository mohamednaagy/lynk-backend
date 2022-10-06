<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\ListSettings;
use App\Enums\Area;
use Modules\Otpify\Facades\Otpify;

class ListSettingsAction implements ListSettings
{
    /**
     * UpdateSettingsAction constructor.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     */
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
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
