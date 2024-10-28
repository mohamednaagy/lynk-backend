<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Models\CommodityType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildPaginatedCommodityTypeQueryAction implements BuildPaginatedCommodityTypeQuery
{
    private $status;
    private $name;

    public function handle(): LengthAwarePaginator
    {
        return CommodityType::query()->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })->when($this->name, function ($query) {
            $query->where('name', 'like', "%{$this->name}%");
        })->paginate();
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
