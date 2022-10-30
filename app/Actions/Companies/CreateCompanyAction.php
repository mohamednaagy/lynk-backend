<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
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
        return Company::create(
            Arr::only(
                $data,
                [
                    'name',
                    'unique_name',
                    'company_cr',
                    'status',
                    'order_cost',
                    'does_order_require_approval',
                ]
            )
        );
    }
}
