<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpdateCompanyAction implements UpdateCompany
{
    /**
     * @param  Company  $company
     * @param  array  $data
     * @return Company
     */
    public function handle(Company $company, array $data): Company
    {
        if (isset($data['webhook_url']) || isset($data['webhook_type'])) {
            $data['webhook_secret_key'] = base64_encode(Str::random(10));
        }

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
                    'webhook_url',
                    'webhook_secret_key',
                    'webhook_type',
                ]
            )
        );

        return $company;
    }
}
