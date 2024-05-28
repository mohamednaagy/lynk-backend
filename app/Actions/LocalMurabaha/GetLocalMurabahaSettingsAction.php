<?php

namespace App\Actions\ProjectSettings;

use App\Actions\Contracts\LocalMurabaha\GetLocalMurabahaSettings;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Settings\Classes\LocalMurabahaSettings;
use App\Support\ProjectSettings\Project;

class GetLocalMurabahaSettingsAction implements GetLocalMurabahaSettings
{
    public function handle(): Project
    {
        $projectSettings = app(LocalMurabahaSettings::class);

        return Project::fromArray($projectSettings->toArray());
    }
}
