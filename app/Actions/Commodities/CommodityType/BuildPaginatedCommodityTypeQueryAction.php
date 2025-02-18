<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Models\CommodityType;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommodityTypeQueryAction implements BuildPaginatedCommodityTypeQuery
{
    private $status;

    private $name;

    private ?int $active = null;

    public function handle(): Builder
    {
        return CommodityType::when($this->status, function ($query) {
            $query->where('status', $this->status);
        })->when($this->name, function ($query) {
            $query->where('name', 'like', "%{$this->name}%");
        })->when($this->active, fn ($q) => $q->active($this->active));
    }

    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param  string|null  $name
     * @return $this
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the active filter status.
     *
     * @param  int|null  $value  The active filter value:
     *                           1 = Active
     *                           2 = Inactive
     *                           3/null = All
     * @return $this
     */
    public function setActive(?int $value): self
    {
        $this->active = $value;

        return $this;
    }
}
