<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class LenderSettings extends Settings
{
    public int $default_company_registration_status;

    public int $default_company_status_created_by_operation;

    public bool $default_does_order_require_approval;

    public bool $email_verification_enabled;

    public float $default_order_cost;

    public bool $notify_about_new_orders;

    public static function group(): string
    {
        return 'area_lender';
    }
}
