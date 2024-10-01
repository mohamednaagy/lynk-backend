<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface PendingEligibleCommodities
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
