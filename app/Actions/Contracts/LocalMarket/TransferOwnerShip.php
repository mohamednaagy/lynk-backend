<?php

namespace App\Actions\Contracts\LocalMarket;

interface TransferOwnerShip
{
    public function handle(string $reference): void;
}
