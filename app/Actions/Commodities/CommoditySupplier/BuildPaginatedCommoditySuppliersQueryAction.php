<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommoditySuppliersQueryAction implements BuildPaginatedCommoditySuppliersQuery
{
    public function handle(): Builder
    {
        return Company::query()
            ->when($this->type, function ($query) {
                $query->type($this->type);
            });
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;

    }
}
