<?php

namespace App\Actions\Contracts\Traders;

interface ListOrdersByTrader
{
    public function handle(string $trader);
}
