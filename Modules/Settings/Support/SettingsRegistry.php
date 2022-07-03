<?php

namespace Modules\Settings\Support;

use Spatie\LaravelSettings\Settings;

class SettingsRegistry
{
    protected static array $settings = [];

    /**
     * @param string $key
     * @param Settings $settings
     * @return $this
     */
    public function register(string $key, Settings $settings)
    {
        self::$settings[$key] = $settings;
        return $this;
    }

    /**
     * @param string $key
     * @return Settings
     */
    public static function getSettingByKey(string $key = null): Settings
    {
        if (!array_key_exists($key, self::$settings))
            $key = 'General';

        return self::$settings[$key];
    }
}
