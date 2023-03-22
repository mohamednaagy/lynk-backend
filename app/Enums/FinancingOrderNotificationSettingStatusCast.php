<?php

namespace App\Enums;

use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class FinancingOrderNotificationSettingStatusCast implements SettingsCast
{
    public function get($payload)
    {
        if ($payload === null) {
            return null;
        }

        return new FinancingOrderNotificationSettingStatus($payload);
    }

    public function set($payload)
    {
        return $payload;
    }
}
