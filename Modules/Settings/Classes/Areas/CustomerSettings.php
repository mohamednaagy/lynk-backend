<?php

namespace Modules\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class CustomerSettings extends Settings
{
    public bool $otp_enabled;
    public string $otp_driver;

    public static function group(): string
    {
        return 'area_customer';
    }
}
