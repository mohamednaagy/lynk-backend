<?php

namespace App\Actions\Contracts\Supplier\CommodityItem;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

interface BuildPaginatedCommodityItemQuery
{
    public function handle(Supplier|Company $supplier): Builder;

    public function setuniqueName(?string $uniqueName): self;

    public function setName(?string $name): self;

    public function setCommodityTypes(?array $commodityTypes): self;

    public function setDirection(?string $direction = 'asc'): self;

    public function setSort(?string $sort = 'id'): self;
}
