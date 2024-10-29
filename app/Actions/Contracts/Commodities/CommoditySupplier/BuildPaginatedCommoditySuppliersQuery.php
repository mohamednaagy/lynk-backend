<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use Illuminate\Database\Eloquent\Builder;

interface BuildPaginatedCommoditySuppliersQuery
{
    public function handle(): Builder;

    public function setType(string $type): self;

    public function setName(?string $name): self;
}
