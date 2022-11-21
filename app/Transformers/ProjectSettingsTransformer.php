<?php

namespace App\Transformers;

use App\Support\ProjectSettings\Project;
use League\Fractal\TransformerAbstract;

class ProjectSettingsTransformer extends TransformerAbstract
{
    public function transform(Project $project): array
    {
        return [
            'company_name' => $project->getCompanyName(),
            'company_cr' => $project->getCompanyCr(),
            'vat_id' => $project->getVatId(),
            'vat_rate' => $project->getVatRate(),
            'vat_rate_in_percentage' => $project->getVatRateInPercentage(),
            'address_line_one' => $project->getCompanyAddress()->getAddressLineOne(),
            'address_line_two' => $project->getCompanyAddress()->getAddressLineTwo(),
        ];
    }
}
