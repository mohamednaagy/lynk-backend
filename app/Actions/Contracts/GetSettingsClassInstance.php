<?php

namespace App\Actions\Contracts;

use Spatie\LaravelSettings\Settings;

interface GetSettingsClassInstance
{
    public function handle(string $key): Settings;
}
