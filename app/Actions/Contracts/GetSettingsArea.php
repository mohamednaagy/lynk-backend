<?php

namespace App\Actions\Contracts;

use Spatie\LaravelSettings\Settings;

interface GetSettingsArea
{
    /**
     * @param string $key
     * @return Settings
     */
    public function handle(string $key): SettingsInterface;
}
