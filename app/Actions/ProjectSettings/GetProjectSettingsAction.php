<?php

namespace App\Actions\LocalMurabaha;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Settings\Classes\ProjectSettings;
use App\Support\ProjectSettings\Project;

class GetLocalMurabahaSettingsAction implements GetProjectSettings
{
    public function handle(): Project
    {
        $projectSettings = app(ProjectSettings::class);

        return Project::fromArray($projectSettings->toArray());
    }
}
