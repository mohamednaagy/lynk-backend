<?php

namespace App\Transformers;

use App\Enums\CompanyStatus;
use App\Models\Company;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    public function transform(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'status_value' => $company->status,
            'status_description' => CompanyStatus::fromValue($company->status)->key,
            'orders_count' => $company->orders->count(),
            'created_at' => $company->created_at->format('Y-m-d'),
        ];
    }
}
