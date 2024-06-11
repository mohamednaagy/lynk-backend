<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class LocalMurabahaSettings extends Settings
{
    public int $default_trade_order_roatation_count;

    public static function group(): string
    {
        return 'local_murabaha';
    }
}
