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
                    'does_order_require_approval',
                    'webhook_secret_key',
                    'public_sFtatus_comment',
                    'internal_status_comment',
                    'driver',
                    'type',
                    'notify_admins_about_new_orders',
                    'notify_borrowers_about_order_updates',
                    'trading_mode',
                    'require_initiate_trade_request',
                ]
            )
        );
    }
}
