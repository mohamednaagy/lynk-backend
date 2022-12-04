<?php

namespace Tests\Traits;

use App\Actions\GetSettingsClassInstanceAction;
use Spatie\LaravelSettings\Settings;

trait InteractsWithSettings
{
    public function getSettingsClass(string $area): Settings
    {
        return (new GetSettingsClassInstanceAction())->handle($area);
    }
}
