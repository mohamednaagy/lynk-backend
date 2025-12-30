<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildSupplierMonthlyUsageQuery;
use App\Jobs\Reports\Enums\ReportType;
use App\Models\Company;
use App\Models\Media;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class BuildSupplierMonthlyUsageQueryAction implements BuildSupplierMonthlyUsageQuery
{
    protected Supplier $supplier;

    protected string $dateFrom = '';

    protected string $dateTo = '';

    public function handle(): Builder
    {
        $query = Media::query()
            ->where('model_type', Company::class)
            ->where('model_id', $this->supplier->id)
            ->where('collection_name', ReportType::SupplierMonthlyUsage);

        if ($this->dateFrom) {
            $query->where('created_at', '>=', toUtc(Carbon::parse($this->dateFrom, 'Asia/Riyadh')->startOfDay()));
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', toUtc(Carbon::parse($this->dateTo, 'Asia/Riyadh')->endOfDay()));
        }

        return $query->latest('id');
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function setDateFrom(?string $dateFrom): self
    {
        if ($dateFrom) {
            $this->dateFrom = $dateFrom;
        }

        return $this;
    }

    public function setDateTo(?string $dateTo): self
    {
        if ($dateTo) {
            $this->dateTo = $dateTo;
        }

        return $this;
    }
}
