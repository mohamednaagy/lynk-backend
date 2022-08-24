<?php

namespace App\Actions\Contracts;

use Spatie\LaravelSettings\Settings;

interface GetSettingsClassInstance
{
    /**
     * @param string $key
     * @return Settings
     */
    public function handle(string $key): Settings;
}
