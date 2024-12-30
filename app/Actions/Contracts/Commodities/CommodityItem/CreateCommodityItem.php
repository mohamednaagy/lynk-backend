<?php

namespace App\Actions\Contracts\Commodities\CommodityItem;

use App\Models\CommodityItem;

interface CreateCommodityItem
{
    public function handle(array $data): CommodityItem;
}
