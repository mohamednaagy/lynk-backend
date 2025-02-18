<?php

namespace App\Actions\Contracts\LocalMarket;

interface ConfirmDeliverProducts
{
    public function handle(string $reference): void;
}
