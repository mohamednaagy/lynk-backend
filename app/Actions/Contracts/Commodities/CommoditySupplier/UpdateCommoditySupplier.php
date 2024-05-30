<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\Supplier;

interface UpdateCommoditySupplier
{
    public function handle(Supplier $commoditySupplier, array $data): Supplier;
}
