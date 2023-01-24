<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\GenerateCompanyCr;
use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Models\Company;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    public function __construct(
        protected GenerateWebhookSecretKey $generateWebhookSecretKey,
        protected GenerateCompanyCr $generateCompanyCr
    ) {
    }

    /**
     * @param  array  $data
     * @return Company
     */
    public function handle(array $data): Company
    {
        if (empty($data['webhook_secret_key'])) {
            $data['webhook_secret_key'] = $this->generateWebhookSecretKey->handle();
        }

        if (empty($data['company_cr'])) {
            $data['company_cr'] = $this->generateCompanyCr->handle();
        }

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
                    'public_status_comment',
                    'internal_status_comment',
                    'driver',
                    'type',
                ]
            )
        );
    }
}
