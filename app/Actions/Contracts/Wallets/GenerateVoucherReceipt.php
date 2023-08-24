<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Transaction;
use Cknow\Money\Money;

interface GenerateVoucherReceipt
{
    public function handle(Transaction $transaction, Money $amount);
}
