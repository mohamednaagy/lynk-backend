<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface BuyCommodities
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
