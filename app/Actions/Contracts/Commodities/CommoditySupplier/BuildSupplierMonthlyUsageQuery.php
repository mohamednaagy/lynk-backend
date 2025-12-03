<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

interface BuildSupplierMonthlyUsageQuery
{
    public function handle(): Builder;

    public function setSupplier(Supplier $supplier): self;
}
