<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildSupplierMonthlyUsageQuery;
use App\Jobs\Reports\Enums\ReportType;
use App\Models\Company;
use App\Models\Media;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class BuildSupplierMonthlyUsageQueryAction implements BuildSupplierMonthlyUsageQuery
{
    protected Supplier $supplier;

    public function handle(): Builder
    {
        return Media::query()
            ->where('model_type', Company::class)
            ->where('model_id', $this->supplier->id)
            ->where('collection_name', ReportType::SupplierMonthlyUsage)
            ->latest('id');
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }
}
