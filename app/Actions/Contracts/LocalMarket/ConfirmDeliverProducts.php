<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface ConfirmDeliverProducts
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
