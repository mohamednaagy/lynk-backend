<?php

namespace App\Transformers;

use App\Models\Company;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    public function transform(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'status' => [
                'value' => $company->status->value,
                'description' => $company->status->description,
            ],
            'orders_count' => $company->orders_count,
            'created_at' => $company->created_at->format('Y-m-d'),
        ];
    }
}
