<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateCompanyAction implements CreateCompany
{
    /**
     * @param  array  $data
     * @return Company
     */
    public function handle(array $data): Company
    {
        // __REVIEW__ if webhook_secret_key is not included in $data, use Str::random(40) as default
        $data['webhook_secret_key'] = Str::random(40);

        // __REVIEW__ public_status_comment & internal_status_comment should be included here
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
                    'webhook_secret_key',
                ]
            )
        );
    }
}
