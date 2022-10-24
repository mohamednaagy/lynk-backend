<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class LenderSettings extends Settings
{
    public bool $email_verification_enabled;

    public static function group(): string
    {
        return 'area_lender';
    }
}
