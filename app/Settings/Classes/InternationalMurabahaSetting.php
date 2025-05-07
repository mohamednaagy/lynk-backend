<?php

namespace App\Settings\Classes;

use Spatie\LaravelSettings\Settings;

class InternationalMurabahaSetting extends Settings
{
    public int $bursam_default_preferred_commodity_type;

    public static function group(): string
    {
        return 'international_murabaha';
    }
}
