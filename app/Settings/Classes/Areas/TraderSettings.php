<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class TraderSettings extends Settings
{
    public int $default_company_status_created_by_operation;

    public float $default_order_cost;

    public static function group(): string
    {
        return 'area_trader';
    }
}
