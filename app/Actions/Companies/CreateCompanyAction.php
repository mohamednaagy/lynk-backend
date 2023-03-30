<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Models\Company;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    public function __construct(
        protected GenerateWebhookSecretKey $generateWebhookSecretKey
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

        return Company::create(
            Arr::only(
                $data,
                [
                    'name',
                    'notifications_email',
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
                    'notify_admins_about_new_orders',
                ]
            )
        );
    }
}
