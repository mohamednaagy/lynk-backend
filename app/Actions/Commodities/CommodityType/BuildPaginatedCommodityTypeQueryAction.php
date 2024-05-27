<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Models\CommodityType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildPaginatedCommodityTypeQueryAction implements BuildPaginatedCommodityTypeQuery
{
    private $status;

    public function handle(): LengthAwarePaginator
    {
        return CommodityType::query()->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })->paginate();
    }

    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}
