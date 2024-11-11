<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface CreateLocalMarketOrder
{
    public function handle(array $data): LocalMarketOrder;
}
