<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\Company;

interface CreateCommoditySupplier
{
    public function handle(array $data): Company;
}
