<?php

namespace App\Actions\Contracts\Commodities\CommodityType;

use Illuminate\Database\Eloquent\Builder;

interface BuildPaginatedCommodityTypeQuery
{
    public function handle(): Builder;

    public function setStatus($status = null): self;

    public function setName(?string $name = null): self;

    public function setActive(?int $value): self;

    public function setProvider(?string $provider = null): self;

    public function setCompanyId(?int $companyId): self;

    public function setUniqueName(?string $uniqueName = null): self;
}
