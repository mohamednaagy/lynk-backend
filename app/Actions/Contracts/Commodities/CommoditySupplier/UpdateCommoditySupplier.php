<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\CommoditySupplier;

interface UpdateCommoditySupplier
{
    public function handle(CommoditySupplier $commoditySupplier, array $data): CommoditySupplier;
}
