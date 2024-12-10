<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface DeliverProducts
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
