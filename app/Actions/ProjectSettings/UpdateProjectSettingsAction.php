<?php

namespace App\Actions\ProjectSettings;

use App\Actions\Contracts\ProjectSettings\UpdateProjectSettings;
use App\Settings\Classes\ProjectSettings;
use App\Support\ProjectSettings\Project;

class UpdateProjectSettingsAction implements UpdateProjectSettings
{
    public function handle(array $data): Project
    {
        $projectSettings = app(ProjectSettings::class);
        $projectSettings->project = Project::fromArray($data);
        $projectSettings->save();

        return $projectSettings->project;
    }
}
