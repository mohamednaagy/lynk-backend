<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\Supplier;

interface CreateCommoditySupplier
{
    public function handle(array $data): Supplier;
}
