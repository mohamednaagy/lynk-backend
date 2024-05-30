<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class LocalMurabahaSettingsAction implements SettingsInterface
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

        $settingInstance->default_trade_order_roatation_count = $data['default_trade_order_roatation_count'];

        $settingInstance->save();
    }
}
