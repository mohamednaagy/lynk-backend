<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class LenderSettings extends Settings
{
    public int $default_company_registration_status;

    public int $default_company_created_by_operation_status;

    public bool $default_does_order_require_approval;

    public bool $email_verification_enabled;

    public float $default_order_cost;

    public static function group(): string
    {
        return 'area_lender';
    }
}
