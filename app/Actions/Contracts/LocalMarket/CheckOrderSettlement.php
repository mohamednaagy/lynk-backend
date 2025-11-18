<?php

namespace App\Actions\Contracts\LocalMarket;

interface CheckOrderSettlement
{
    public function handle(string $reference): bool;
}
