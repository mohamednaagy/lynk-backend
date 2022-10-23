<?php

namespace App\Actions\Contracts\Wallets;

interface CalculateOrdersCost
{
    public function handle(array $data): float|int;
}
