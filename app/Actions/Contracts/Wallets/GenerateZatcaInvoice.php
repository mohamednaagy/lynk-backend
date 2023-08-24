<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\TraderOrder;
use App\Models\Transaction;

interface GenerateZatcaInvoice
{
    public function handle(TraderOrder $traderOrder, Transaction $creationFeeTransaction);
}
