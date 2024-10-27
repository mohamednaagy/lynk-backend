<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface CancelOrder
{
    public function handle(LocalMarketOrder $localMarketOrder);
}
