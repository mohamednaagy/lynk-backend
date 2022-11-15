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
        $project = Project::fromArray($data);

        $projectSettings->company_name = $project->getCompanyName();
        $projectSettings->company_cr = $project->getCompanyCr();
        $projectSettings->vat_id = $project->getVatId();
        $projectSettings->vat_rate = $project->getVatRate();
        $projectSettings->address_line_one = $project->getCompanyAddress()->getAddressLineOne();
        $projectSettings->address_line_two = $project->getCompanyAddress()->getAddressLineTwo();

        $projectSettings->save();

        return $project;
    }
}
