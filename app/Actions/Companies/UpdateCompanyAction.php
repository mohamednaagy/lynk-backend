<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Models\Company;
use Cknow\Money\Money;
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
        if (isset($data['order_cost'])) {
            $data['order_cost'] = Money::parseByDecimal($data['order_cost'], Money::getDefaultCurrency());
        }

        $company->update(
            Arr::only(
                $data,
                [
                    'name',
                    'email',
                    'unique_name',
                    'company_cr',
                    'status',
                    'order_cost',
                    'does_order_require_approval',
                    'webhook_secret_key',
                    'public_status_comment',
                    'internal_status_comment',
                    'driver',
                ]
            )
        );

        return $company;
    }
}
