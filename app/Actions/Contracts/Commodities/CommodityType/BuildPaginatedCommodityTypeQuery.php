<?php

namespace App\Actions\Contracts\Commodities\CommodityType;

use Illuminate\Database\Eloquent\Builder;


interface BuildPaginatedCommodityTypeQuery
{
    public function handle(): Builder;

    public function setStatus($status = null);

    public function setName($name = null);
}
