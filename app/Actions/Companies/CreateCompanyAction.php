<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Models\Lender;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    public function __construct(
        protected GenerateWebhookSecretKey $generateWebhookSecretKey
    ) {}

    public function handle(array $data): Lender
    {
        if (empty($data['webhook_secret_key'])) {
            $data['webhook_secret_key'] = $this->generateWebhookSecretKey->handle();
        }

        if (array_key_exists('require_initiate_trade_request', $data) && is_null($data['require_initiate_trade_request'])) {
            unset($data['require_initiate_trade_request']);
        }

        $lender = Lender::create(
            Arr::only(
                $data,
                [
                    'name',
                    'unique_name',
                    'status',
                    'webhook_secret_key',
                    'public_status_comment',
                    'driver',
                    'type',
                    'force_unique_reference_number',
                    'require_initiate_trade_request',
                ]
            )
        );

        $lender->lenderDetail()->create(
            Arr::only(
                $data,
                [
                    'force_preferred_commodity_type',
                    'notifications_email',
                    'company_cr',
                    'default_contract_sign_time_limit',
                    'contract_number',
                    'preferred_market_type',
                    'does_order_require_approval',
                    'notify_admins_about_new_orders',
                    'notify_borrowers_about_order_updates',
                    'require_initiate_trade_request',
                    'trading_mode',
                    'internal_status_comment',
                    'public_status_comment',
                    'auto_complete_murabaha_order',
                ]
            )
        );

        if (isset($data['preferred_commodity_types']) && ! empty($data['preferred_commodity_types'])) {
            $lender->commodityTypes()->attach($data['preferred_commodity_types']);
        }

        return $lender;
    }
}
