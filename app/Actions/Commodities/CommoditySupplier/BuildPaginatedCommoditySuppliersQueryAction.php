<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommoditySuppliersQueryAction implements BuildPaginatedCommoditySuppliersQuery
{
    private string $type;
    private ?string $name;
    public function handle(): Builder
    {
        return Company::when($this->type, function ($query) {
                $query->type($this->type);
            })->when($this->name, function ($query) {
                $query->name($this->name);
            });
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;

    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
