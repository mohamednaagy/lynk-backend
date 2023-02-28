<?php

namespace App\Settings\Classes;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $otp_driver;

    public string $order_cost;

    public int $trader_order_timeout;

    public static function group(): string
    {
        return 'general';
    }
}
