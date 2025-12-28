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

    protected string $dateFrom;

    protected string $dateTo;

    public function handle(): Builder
    {
        $query = Media::query()
            ->where('model_type', Company::class)
            ->where('model_id', $this->supplier->id)
            ->where('collection_name', ReportType::SupplierMonthlyUsage)
            ->latest('id');

        if ($this->dateFrom) {
            $query->where('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', $this->dateTo);
        }

        return $query;
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function setDateFrom(string $dateFrom): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function setDateTo(string $dateTo): self
    {
        $this->dateTo = $dateTo;

        return $this;
    }
}
