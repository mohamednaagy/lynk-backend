<?php

namespace App\Actions;

use App\Actions\Contracts\CreateCompany;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    /**
     * @param  array  $data
     * @return Company
     */
    public function handle(array $data): Company
    {
        $data['status'] = CompanyStatus::Approved;

        return Company::create(
            Arr::only(
                $data,
                [
                    'name',
                    'unique_name',
                    'company_cr',
                    'status',
                ]
            )
        );
    }
}
