<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\CommoditySupplier;

interface CreateCommoditySupplier
{
    public function handle(array $data): CommoditySupplier;
}
