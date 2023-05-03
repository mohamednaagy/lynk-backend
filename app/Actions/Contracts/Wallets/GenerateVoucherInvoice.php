<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Transaction;

interface GenerateVoucherInvoice
{
    public function handle(Transaction $transaction);
}
