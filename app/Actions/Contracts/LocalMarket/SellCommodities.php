<?php

namespace App\Actions\Contracts\LocalMarket;

interface SellCommodities
{
    public function handle(string $reference): void;
}
