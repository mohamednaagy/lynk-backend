<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\SettingsInterface;

class LenderSettingsAction implements SettingsInterface
{
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
    }

    public function handle(array $data): void
    {
        $settingInstance = $this->getSettingsClassInstance->handle($data['area']);
        $settingInstance->email_verification_enabled = $data['email_verification_enabled'];
        $settingInstance->save();
    }
}
