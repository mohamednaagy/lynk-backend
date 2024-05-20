<?php

namespace App\Transformers;

use App\Models\Company;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CommoditySuppliersTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'legal_name',
        'unique_name',
        'description',
        'status',
        'market_type',
        'created_at',

    ];

    public function transform(Company $company): array
    {
        return [];
    }

    public function includeId(Company $company): Primitive
    {
        return $this->primitive($company->id);
    }

    public function includeLegalName(Company $company): Primitive
    {
        return $this->primitive($company->name);
    }

    public function includeDescription(Company $company): Primitive
    {
        return $this->primitive($company->commoditySupplier->description);
    }

    public function includeUniqueName(Company $company): Primitive
    {
        return $this->primitive($company->unique_name);
    }

    public function includeStatus(Company $company): Primitive
    {
        return $this->primitive([
            'value' => $company->commoditySupplier->status->value,
            'description' => $company->commoditySupplier->status->description,
        ]);
    }

    public function includeMarketType(Company $company): Primitive
    {
        return $this->primitive([
            'value' => $company->commoditySupplier->market_type->value,
            'description' => $company->commoditySupplier->market_type->description,
        ]);
    }

    public function includeCreatedAt(Company $company): Primitive
    {
        return $this->primitive(optional($company->commoditySupplier->created_at)->format('Y-m-d'));
    }
}
