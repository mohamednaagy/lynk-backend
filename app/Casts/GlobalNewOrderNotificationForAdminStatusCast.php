<?php

namespace App\Casts;

use App\Enums\GlobalNewOrderNotificationForAdminStatus;
use BenSampo\Enum\Enum;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class GlobalNewOrderNotificationForAdminStatusCast implements SettingsCast
{
    public function get($payload)
    {
        if ($payload === null) {
            return null;
        }

        return new GlobalNewOrderNotificationForAdminStatus($payload);
    }

    public function set($payload)
    {
        if (! $payload instanceof Enum) {
            $payload = GlobalNewOrderNotificationForAdminStatus::fromValue($payload);
        }

        return GlobalNewOrderNotificationForAdminStatus::serializeDatabase($payload);
    }
}
