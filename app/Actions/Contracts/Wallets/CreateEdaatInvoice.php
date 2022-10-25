<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\EdaatInvoice;

interface CreateEdaatInvoice
{
    public function handle(int $ordersCount, $orderCost): EdaatInvoice;
}
