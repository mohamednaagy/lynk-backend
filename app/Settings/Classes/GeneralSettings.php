<?php

namespace App\Settings\Classes;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $otp_driver;

    public string $order_cost;

    public static function group(): string
    {
        return 'general';
    }
}
