<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Models\Lender;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    public function __construct(
        protected GenerateWebhookSecretKey $generateWebhookSecretKey
    ) {
    }

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
                    'internal_status_comment',
                    'driver',
                    'type',
                    'notify_borrowers_about_order_updates',
                    'force_unique_reference_number',
                    'trading_mode',
                    'require_initiate_trade_request',
                ]
            )
        );
        
        $lender->lenderDetail()->create([
            'force_preferred_commodity_type'    => $data['force_preferred_commodity_type'] ?? false,
            'notifications_email'               => $data['notifications_email'],
            'company_cr'                        => $data['company_cr'],
            'default_contract_sign_time_limit'  => $data['default_contract_sign_time_limit'] ?? null,
            'contract_number'                   => $data['contract_number'] ?? null,
            'preferred_market_type'             => $data['preferred_market_type'] ?? null,
            'does_order_require_approval'       => $data['does_order_require_approval'] ?? null,
            'notify_admins_about_new_orders'    => $data['notify_admins_about_new_orders'] ?? CompanyNewOrderNotificationForAdminStatus::On,
        ]);

        if (isset($data['preferred_commodity_types']) && ! empty($data['preferred_commodity_types'])) {
            $lender->commodityTypes()->attach($data['preferred_commodity_types']);
        }

        return $lender;
    }
}
