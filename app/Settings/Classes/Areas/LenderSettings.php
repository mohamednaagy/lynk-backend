<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class LenderSettings extends Settings
{
    // __REVIEW__ $default_company_registration_status
    public int $company_registration_status;

    // __REVIEW__ $default_company_created_by_operation_status
    public int $company_created_by_operation_status;

    public bool $email_verification_enabled;

    // __REVIEW__ $default_order_cost
    public float $order_cost;

    public static function group(): string
    {
        return 'area_lender';
    }
}
