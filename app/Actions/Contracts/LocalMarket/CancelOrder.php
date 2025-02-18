<?php

namespace App\Actions\Contracts\LocalMarket;

interface CancelOrder
{
    public function handle(string $reference);
}
