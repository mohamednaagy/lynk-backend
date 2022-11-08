<?php

namespace App\Transformers;

use App\Models\Company;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'status',
        'orders_count',
        'created_at',
    ];

    public function transform(Company $company): array
    {
        return [

        ];
    }

    public function includeId(Company $company): Primitive
    {
        return $this->primitive($company->id);
    }

    public function includeName(Company $company): Primitive
    {
        return $this->primitive($company->name);
    }

    public function includeStatus(Company $company): Primitive
    {
        return $this->primitive([
            'value' => $company->status->value,
            'description' => $company->status->description,
        ]);
    }

    public function includeOrderCount(Company $company): Primitive
    {
        return $this->primitive($company->orders_count);
    }

    public function includeCreatedAt(Company $company): Primitive
    {
        return $this->primitive(optional($company->created_at)->format('Y-m-d'));
    }
}
