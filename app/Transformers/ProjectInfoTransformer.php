<?php

namespace App\Transformers;

use App\Support\ProjectSettings\Project;
use League\Fractal\TransformerAbstract;

class ProjectInfoTransformer extends TransformerAbstract
{
    public function transform(Project $project): array
    {
        return [
            'company_name' => $project->getCompanyName(),
            'company_cr' => $project->getCompanyCr(),
            'vat_id' => $project->getVatId(),
            'vat_rate' => round($project->getVatRateInPercentage(), 1),
            'address_line_one' => $project->getCompanyAddress()->getAddressLineOne(),
            'address_line_two' => $project->getCompanyAddress()->getAddressLineTwo(),
        ];
    }
}
