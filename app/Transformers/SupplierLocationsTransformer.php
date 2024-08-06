<?php

namespace App\Transformers;

use App\Models\SupplierLocation;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class SupplierLocationsTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'unique_identifier',
        'name',
        'description',
        'supplier_id',
        'created_at',
        'is_deletable',

    ];

    public function transform(SupplierLocation $supplierLocation): array
    {
        return [];
    }

    public function includeId(SupplierLocation $supplierLocation): Primitive
    {
        return $this->primitive($supplierLocation->id);
    }

    public function includeUniqueIdentifier(SupplierLocation $supplierLocation): Primitive
    {
        return $this->primitive($supplierLocation->unique_identifier);
    }

    public function includeDescription(SupplierLocation $supplierLocation): Primitive
    {
        return $this->primitive($supplierLocation->description);
    }

    public function includeName(SupplierLocation $supplierLocation): Primitive
    {
        return $this->primitive($supplierLocation->name);
    }

    public function includeCreatedAt(SupplierLocation $supplierLocation): Primitive
    {
        return $this->primitive(optional($supplierLocation->created_at)->format('Y-m-d'));
    }

    public function includeIsDeletable(SupplierLocation $supplierLocation): Primitive
    {
        return $this->primitive($supplierLocation->is_deletable);
    }
}
