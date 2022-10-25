<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateEdaatInvoice;
use App\Models\EdaatInvoice;

class CreateEdaatInvoiceAction implements CreateEdaatInvoice
{
    public function handle(int $ordersCount, $orderCost): EdaatInvoice
    {
        $amount = $ordersCount * $orderCost;

        return new EdaatInvoice();
    }
}
