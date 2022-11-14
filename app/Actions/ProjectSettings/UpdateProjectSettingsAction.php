<?php

namespace App\Actions\ProjectSettings;

use App\Actions\Contracts\ProjectSettings\UpdateProjectSettings;
use App\Settings\Classes\ProjectSettings;
use App\Support\ProjectSettings\Project;

class UpdateProjectSettingsAction implements UpdateProjectSettings
{
    public function handle(array $data): Project
    {
        $projectInstance = app(ProjectSettings::class);
        $project = Project::fromArray($data);

        $projectInstance->company_name = $project->getCompanyName();
        $projectInstance->company_cr = $project->getCompanyCr();
        $projectInstance->vat_id = $project->getVatId();
        $projectInstance->vat = $project->getVat();
        $projectInstance->address_line_one = $project->getCompanyAddress()->getAddressLineOne();
        $projectInstance->address_line_two = $project->getCompanyAddress()->getAddressLineTwo();

        $projectInstance->save();

        return $project;
    }
}
