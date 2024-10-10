<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface LocalMarketCancelOrder
{
    public function handle(LocalMarketOrder $localMarketOrder);
}
