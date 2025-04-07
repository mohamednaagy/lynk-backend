<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class SuperAdminSettingsAction implements SettingsInterface
{
    /**
     * UpdateSettingsAction constructor.
     */
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {}

    public function handle(array $data): void
    {
        $settingInstance = $this->getSettingsClassInstance->handle($data['area']);

        $settingInstance->otp_driver = $data['otp_driver'];
        $settingInstance->otp_enabled = $data['otp_enabled'];

        $settingInstance->save();
    }
}
