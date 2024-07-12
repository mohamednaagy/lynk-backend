<?php

namespace Tests\Traits;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\LocalMurabaha\GetLocalMurabahaSettingsAction;

trait InteractsWithSettings
{
    public function getSettingsClass(string $area)
    {
        return app(GetSettingsClassInstance::class)->handle($area);
    }

    public function getLocalMurabahaSettingsClass(string $area)
    {
        return app(GetLocalMurabahaSettingsAction::class)->handle($area);
    }
}
