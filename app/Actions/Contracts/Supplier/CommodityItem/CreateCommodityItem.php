<?php

namespace App\Actions\Contracts\Supplier\CommodityItem;

use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\Supplier;

interface CreateCommodityItem
{
    public function handle(array $data): CommodityItem;

    public function setSupplier(Supplier|Company $supplier): self;
}
