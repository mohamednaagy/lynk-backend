<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class SuperAdminSettings extends Settings
{
    public bool $otp_enabled;

    public string $otp_driver;

    public string $company_wakala_template;

    public string $client_wakala_template;

    public static function group(): string
    {
        return 'area_super_admin';
    }
}
