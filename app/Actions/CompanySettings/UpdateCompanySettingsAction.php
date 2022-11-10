<?php

namespace App\Actions\CompanySettings;

use App\Actions\Contracts\CompanySettings\UpdateCompanySettings;
use App\Settings\Classes\CompanySettings;
use App\Support\CompanySettings\Company;

class UpdateCompanySettingsAction implements UpdateCompanySettings
{
    public function handle(array $data): Company
    {
        $companyInstance = app(CompanySettings::class);
        $company = Company::fromArray($data);

        $companyInstance->company_name = $company->getCompanyName();
        $companyInstance->company_cr = $company->getCompanyCr();
        $companyInstance->vat_id = $company->getVatId();
        $companyInstance->vat = $company->getVat();
        $companyInstance->address_line_one = $company->getCompanyAddress()->getAddressLineOne();
        $companyInstance->address_line_two = $company->getCompanyAddress()->getAddressLineTwo();

        $companyInstance->save();

        return $company;
    }
}
