<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommoditySuppliersQueryAction implements BuildPaginatedCommoditySuppliersQuery
{
    private string $type;

    private ?string $name = null;

    private ?int $status = null;

    public function handle(): Builder
    {
        return Company::when($this->type, function ($query) {
            $query->type($this->type);
        })->when($this->name, function ($query) {
            $query->where('name', 'like', "%{$this->name}%");
        })->when($this->status, function ($query) {
            $query->where('status', $this->status);
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

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
