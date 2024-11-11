<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface TransferOwnerShip
{
    public function handle(LocalMarketOrder $localMarketOrder): void;
}
