<?php

namespace App\Actions\Contracts\Commodities\CommodityItem;

use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\Supplier;

interface UpdateCommodityItem
{
    public function handle(CommodityItem $commodityItem, array $data): CommodityItem;

    public function setSupplier(Supplier|Company $supplier): self;
}

