<?php

namespace App\Actions\Contracts\Commodities\CommodityItem;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedCommodityItems
{
    public function handle(): LengthAwarePaginator;

    public function setuniqueName(?string $uniqueName): self;

    public function setName(?string $name): self;

    public function setSuppliers(?array $suppliers): self;

    public function setCommodityTypes(?array $commodityTypes): self;

    public function setDirection(string $direction = 'asc'): self;

    public function setSort(string $sort = 'id'): self;
}
