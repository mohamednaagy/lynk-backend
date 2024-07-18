<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class LocalMurabahaSettingsAction implements SettingsInterface
{
    /**
     * UpdateSettingsAction constructor.
     */
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
    }

    public function handle(array $data): void
    {
        $settingInstance = $this->getSettingsClassInstance->handle($data['area']);

        $settingInstance->default_trade_order_roatation_count = $data['default_trade_order_roatation_count'];

        // $settingInstance->default_contract_sign_time_limit = $data['default_contract_sign_time_limit'];

        $settingInstance->save();
    }
}
