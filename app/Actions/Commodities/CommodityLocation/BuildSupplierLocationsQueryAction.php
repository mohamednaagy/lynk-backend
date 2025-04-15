<?php

namespace App\Actions\Commodities\CommodityLocation;

use App\Actions\Contracts\Commodities\CommodityLocation\BuildSupplierLocationsQuery;
use App\Models\Supplier;
use App\Models\SupplierLocation;
use Illuminate\Database\Eloquent\Builder;

class BuildSupplierLocationsQueryAction implements BuildSupplierLocationsQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = SupplierLocation::query();
    }

    public function handle(): Builder
    {
        return $this->query;
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->query->where('company_id', $supplier->id);

        return $this;
    }
}
