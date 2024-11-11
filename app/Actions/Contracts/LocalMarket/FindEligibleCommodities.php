<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface FindEligibleCommodities
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
