<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Lender;
use App\Models\TraderOrder;
use App\Models\Transaction;

interface DeductTraderOrderVatPercentage
{
    public function handle(TraderOrder $traderOrder, Transaction $transaction, Lender $lender);
}
