<?php

namespace App\Actions;

use App\Actions\Contracts\SettingsInterface;
use App\Actions\Contracts\GetSettingsClassInstance;

class SuperAdminSettingsAction implements SettingsInterface
{
    protected GetSettingsClassInstance $getSettingsClassInstance;
    /**
     * UpdateSettingsAction constructor.
     * @param GetSettingsClassInstance $getSettingsClassInstance
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance)
    {
        $this->getSettingsClassInstance = $getSettingsClassInstance;
    }

    /**
     * @param array $data
     * @return void
     */
    public function handle(array $data): void
    {
        $settingInstance = $this->getSettingsClassInstance->handle($data['area']);

        $settingInstance->otp_driver = $data['otp_driver'];
        $settingInstance->otp_enabled = $data['otp_enabled'];

        $settingInstance->save();
    }
}
