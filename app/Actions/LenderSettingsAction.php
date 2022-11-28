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

        $settingInstance->default_company_registration_status = $data['default_company_registration_status'];
        $settingInstance->default_company_status_created_by_operation = $data['default_company_status_created_by_operation'];
        $settingInstance->default_does_order_require_approval = $data['default_does_order_require_approval'];
        $settingInstance->email_verification_enabled = $data['email_verification_enabled'];
        $settingInstance->default_order_cost = $data['default_order_cost'];

        $settingInstance->save();
    }
}
