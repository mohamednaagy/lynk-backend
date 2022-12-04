<?php

namespace Tests\Traits;

use App\Actions\Contracts\GetSettingsClassInstance;

trait InteractsWithSettings
{
    public function getSettingsClass(string $area)
    {
        return app(GetSettingsClassInstance::class)->handle($area);
    }
}
