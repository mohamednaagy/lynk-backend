<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Models\CommodityType;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommodityTypeQueryAction implements BuildPaginatedCommodityTypeQuery
{
    private $status;
    private $name;

    public function handle(): Builder
    {
        return CommodityType::when($this->status, function ($query) {
            $query->where('status', $this->status);
        })->when($this->name, function ($query) {
            $query->where('name', 'like', "%{$this->name}%");
        });
    }

    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param string|null $name
     * @return $this
     */

    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }
}
