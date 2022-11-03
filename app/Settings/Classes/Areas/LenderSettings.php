<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class LenderSettings extends Settings
{
    public int $company_registration_status;

    public int $company_created_by_operation_status;

    public bool $email_verification_enabled;

    public float $order_cost;

    public static function group(): string
    {
        return 'area_lender';
    }
}
