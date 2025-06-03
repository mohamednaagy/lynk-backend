<?php

namespace App\Actions\Contracts\Commodities\CommodityType;

use Illuminate\Database\Eloquent\Builder;

interface BuildPaginatedCommodityTypeQuery
{
    public function handle(): Builder;

    public function setStatus($status = null);

    public function setName($name = null);

    public function setActive(?int $value): self;

    public function setProvider($provider = null);
}
