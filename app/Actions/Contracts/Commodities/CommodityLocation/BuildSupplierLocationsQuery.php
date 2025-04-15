<?php

namespace App\Actions\Contracts\Commodities\CommodityLocation;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

interface BuildSupplierLocationsQuery
{
    public function handle(): Builder;

    public function setSupplier(Supplier $supplier): self;
}
