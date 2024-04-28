<?php

namespace App\Actions\Contracts\Commodities\CommodityType;

use App\Models\CommodityType;

interface CreateCommodityType
{
    public function handle(array $data): CommodityType;
}
