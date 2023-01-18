<?php

namespace App\Settings\Classes\Areas;

use Spatie\LaravelSettings\Settings;

class TraderSettings extends Settings
{
    public static function group(): string
    {
        return 'area_trader';
    }
}
