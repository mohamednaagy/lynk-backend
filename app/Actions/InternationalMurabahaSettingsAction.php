<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class InternationalMurabahaSettingsAction implements SettingsInterface
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

        $settingInstance->bursam_default_preferred_commodity_type = $data['bursam_default_preferred_commodity_type'];

        $settingInstance->save();
    }
}
