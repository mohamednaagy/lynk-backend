<?php

namespace App\Settings\Classes;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $default_otp_driver;

    public static function group(): string
    {
        return 'general';
    }
}
