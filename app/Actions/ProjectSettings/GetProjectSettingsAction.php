<?php

namespace App\Actions\ProjectSettings;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Settings\Classes\ProjectSettings;
use App\Support\ProjectSettings\Project;

class GetProjectSettingsAction implements GetProjectSettings
{
    public function handle(): Project
    {
        $projectSettings = app(ProjectSettings::class);

        return Project::fromArray($projectSettings->toArray());
    }
}
