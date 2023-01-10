<?php

namespace App\Actions\Contracts\Traders;

interface GetOrder
{
    public function handle(int $order);
}
