<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class GeneralSettingsAction implements SettingsInterface
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
        $settingInstance->order_cost = $data['order_cost'];
        $settingInstance->order_responsible_admins = $data['order_responsible_admins'];

        $settingInstance->save();
    }
}
