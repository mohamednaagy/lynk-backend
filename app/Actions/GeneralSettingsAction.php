<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class GeneralSettingsAction implements SettingsInterface
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
     * @param  array  $data
     * @return void
     */
    public function handle(array $data): void
    {
        $settingInstance = $this->getSettingsClassInstance->handle($data['area']);

        $settingInstance->otp_driver = $data['otp_driver'];
        $settingInstance->order_cost = $data['order_cost'];
        $settingInstance->order_responsible_admins = $data['order_responsible_admins'];

        $settingInstance->save();
    }
}
