<?php

namespace App\Transformers;

use App\Support\CompanySettings\Company;
use League\Fractal\TransformerAbstract;

class CompanyInfoTransformer extends TransformerAbstract
{
    public function transform(Company $company): array
    {
        return [
            'company_name' => $company->getCompanyName(),
            'company_cr' => $company->getCompanyCr(),
            'vat_id' => $company->getVatId(),
            'vat' => round($company->getVatToShow(), 1),
            'address_line_one' => $company->getCompanyAddress()->getAddressLineOne(),
            'address_line_two' => $company->getCompanyAddress()->getAddressLineTwo(),
        ];
    }
}
