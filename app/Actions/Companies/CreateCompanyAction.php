<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Models\Company;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    public function __construct(protected GenerateWebhookSecretKey $generateWebhookSecretKey)
    {
    }

    /**
     * @param  array  $data
     * @return Company
     */
    public function handle(array $data): Company
    {
        $data['webhook_secret_key'] = $this->generateWebhookSecretKey->handle();

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
