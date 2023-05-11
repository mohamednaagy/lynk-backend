<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Transaction;

interface GenerateVoucherReceipt
{
    public function handle(Transaction $transaction);
}
