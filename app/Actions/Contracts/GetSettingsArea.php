<?php

namespace App\Actions\Contracts;

use Spatie\LaravelSettings\Settings;

interface GetSettingsArea
{
    /**
     * @param string $key
     * @return SettingsInterface
     */
    public function handle(string $key): SettingsInterface;
}
