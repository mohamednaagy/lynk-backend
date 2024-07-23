<?php

namespace App\Actions\Contracts\Supplier\CommodityItem\Inventory;

use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Models\Supplier;

interface CreateLocalMarketInventory
{
    public function handle(array $data): LocalMarketInventory;

    public function setSupplier(Supplier|Company $supplier): self;

    public function setItem(CommodityItem $item): self;
}
