<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Models\Company;
use Illuminate\Support\Arr;

class UpdateCompanyAction implements UpdateCompany
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
                    'name',
                    'unique_name',
                    'company_cr',
                    'status',
                    'order_cost',
                    'does_order_require_approval',
                    'public_status_comment',
                    'internal_status_comment',
                    'webhook_secret_key',
                ]
            )
        );

        return $company;
    }
}
