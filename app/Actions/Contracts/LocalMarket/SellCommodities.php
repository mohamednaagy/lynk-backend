<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface SellCommodities
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
