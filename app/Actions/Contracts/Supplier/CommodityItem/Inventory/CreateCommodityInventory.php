<?php

namespace App\Actions\Contracts\Supplier\CommodityItem\Inventory;

use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Supplier;

interface CreateCommodityInventory
{
    public function handle(array $data): Inventory;

    public function setSupplier(Supplier|Company $supplier): self;

    public function setItem(CommodityItem $item): self;
}
