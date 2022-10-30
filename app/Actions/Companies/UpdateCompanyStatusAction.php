<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\UpdateCompanyStatus;
use App\Models\Company;
use Illuminate\Support\Arr;

class UpdateCompanyStatusAction implements UpdateCompanyStatus
{
    /**
     * @param  Company  $company
     * @param  array  $data
     * @return Company
     */
    public function handle(Company $company, array $data): Company
    {
        $company->update(
            Arr::only(
                $data,
                [
                    'status',
                    'to_company_comment',
                    'internal_comment',
                ]
            )
        );

        return $company;
    }
}
