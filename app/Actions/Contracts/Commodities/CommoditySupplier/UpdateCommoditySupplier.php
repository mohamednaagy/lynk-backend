<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\Company;

interface UpdateCommoditySupplier
{
    public function handle(Company $commoditySupplier, array $data): Company;
}
