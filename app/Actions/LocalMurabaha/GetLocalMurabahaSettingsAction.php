<?php

namespace App\Actions\ProjectSettings;

use App\Actions\Contracts\LocalMurabaha\GetLocalMurabahaSettings;
use App\Settings\Classes\LocalMurabahaSettings;
use App\Support\ProjectSettings\Project;

class GetLocalMurabahaSettingsAction
{
    public function handle(): Project
    {
        $projectSettings = app(LocalMurabahaSettings::class);

        return Project::fromArray($projectSettings->toArray());
    }
}
