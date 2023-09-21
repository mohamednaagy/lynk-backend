<?php

namespace App\Transformers;

use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;
use Spatie\LaravelSettings\Settings;

class LenderSettingsTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'default_order_cost',
        'email_verification_enabled',
        'default_does_order_require_approval',
        'default_company_registration_status',
        'default_company_status_created_by_operation',
        'notify_admins_about_new_orders',
        'require_initiate_trade_request',
    ];

    public function transform(Settings $settings): array
    {
        return [
        ];
    }

    public function includeEmailVerificationEnabled(Settings $settings): Primitive
    {
        return $this->primitive($settings->email_verification_enabled);
    }

    public function includeDefaultOrderCost(Settings $settings): Primitive
    {
        return $this->primitive($settings->default_order_cost);
    }

    public function includeDefaultDoesOrderRequireApproval(Settings $settings): Primitive
    {
        return $this->primitive($settings->default_does_order_require_approval);
    }

    public function includeDefaultCompanyRegistrationStatus(Settings $settings): Primitive
    {
        return $this->primitive($settings->default_company_registration_status);
    }

    public function includeDefaultCompanyStatusCreatedByOperation(Settings $settings): Primitive
    {
        return $this->primitive($settings->default_company_status_created_by_operation);
    }

    public function includeNotifyAdminsAboutNewOrders(Settings $settings): Primitive
    {
        return $this->primitive($settings->notify_admins_about_new_orders);
    }

    public function includeRequireInitiateTradeRequest(Settings $settings): Primitive
    {
        return $this->primitive($settings->require_initiate_trade_request);
    }
}
