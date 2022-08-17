<?php

namespace Modules\Settings\Support;

use Modules\Settings\Services\Contracts\SettingsInterface;
use Spatie\LaravelSettings\Settings;

class SettingsRegistry
{
    protected static array $settingInstances = [];
    protected static array $settingServices = [];

    /**
     * @param string $key
     * @param Settings $settings
     * @return $this
     */
    public function register(string $key, Settings $settings, SettingsInterface $settingService)
    {
        self::$settingInstances[$key] = $settings;
        self::$settingServices[$key] = $settingService;

        return $this;
    }

    /**
     * @param string $key
     * @return Settings
     */
    public static function getSettingInstanceByKey(string $key = null): Settings
    {
        if (!array_key_exists($key, self::$settingInstances))
            $key = 'General';

        return self::$settingInstances[$key];
    }

    /**
     * @param string|null $key
     * @return SettingsInterface
     */
    public static function getSettingServiceByKey(string $key = null): SettingsInterface
    {
        if (!array_key_exists($key, self::$settingServices))
            $key = 'General';

        return self::$settingServices[$key];
    }
}
