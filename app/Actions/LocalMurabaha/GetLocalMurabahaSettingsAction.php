<?php

namespace App\Actions\LocalMurabaha;

use App\Actions\Contracts\LocalMurabaha\GetLocalMurabahaSettings;
use App\Settings\Classes\LocalMurabahaSettings;
use App\Support\LocalMurabahaSettings\LocalMurabaha;

class GetLocalMurabahaSettingsAction implements GetLocalMurabahaSettings
{
    public function handle()
    {
        $murabahaSettings = app(LocalMurabahaSettings::class);

        return LocalMurabaha::fromArray($murabahaSettings->toArray());

    }
}
