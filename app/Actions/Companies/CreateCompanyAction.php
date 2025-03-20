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
                    'internal_status_comment',
                    'driver',
                    'type',
                    'force_unique_reference_number',
                    'trading_mode',
                    'require_initiate_trade_request',
                ]
            )
        );

        $lender->lenderDetail()->create([
            'notifications_email' => $data['notifications_email'],
            'company_cr' => $data['company_cr'],
            'contract_number' => $data['contract_number'] ?? null,
            'preferred_market_type' => $data['preferred_market_type'] ?? null,
            'does_order_require_approval' => $data['does_order_require_approval'] ?? false,
            'notify_admins_about_new_orders' => $data['notify_admins_about_new_orders'] ?? true,
            'notify_borrowers_about_order_updates' => $data['notify_borrowers_about_order_updates'] ?? false,
        ]);

        if (isset($data['preferred_commodity_types']) && ! empty($data['preferred_commodity_types'])) {
            $lender->commodityTypes()->attach($data['preferred_commodity_types']);
        }

        return $lender;
    }
}
