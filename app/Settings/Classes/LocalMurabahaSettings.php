<?php

namespace App\Settings\Classes;

use Spatie\LaravelSettings\Settings;

class LocalMurabahaSettings extends Settings
{
    public int $default_trade_order_rotation_count;

    public int $default_contract_sign_time_limit;

    public static function group(): string
    {
        return 'local_murabaha';
    }
}
