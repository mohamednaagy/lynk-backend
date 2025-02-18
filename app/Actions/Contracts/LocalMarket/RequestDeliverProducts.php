<?php

namespace App\Actions\Contracts\LocalMarket;

interface RequestDeliverProducts
{
    public function handle(string $reference): void;
}
