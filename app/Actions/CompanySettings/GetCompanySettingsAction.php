<?php

namespace App\Actions\CompanySettings;

use App\Actions\Contracts\CompanySettings\GetCompanySettings;
use App\Settings\Classes\CompanySettings;
use App\Support\CompanySettings\Company;

class GetCompanySettingsAction implements GetCompanySettings
{
    public function handle(): Company
    {
        $companyInstance = app(CompanySettings::class);

        return Company::fromArray($companyInstance->toArray());
    }
}
