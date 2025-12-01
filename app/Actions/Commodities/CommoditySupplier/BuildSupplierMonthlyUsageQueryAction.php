<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildSupplierMonthlyUsageQuery;
use App\Models\Company;
use App\Models\Media;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class BuildSupplierMonthlyUsageQueryAction implements BuildSupplierMonthlyUsageQuery
{
    protected Supplier $supplier;

    protected string $type;

    public function handle(): Builder
    {
        return Media::query()
            ->where('model_type', Company::class)
            ->where('model_id', $this->supplier->id)
            ->where('collection_name', $this->type)
            ->latest('id');
    }

    public function setSupplier(Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
