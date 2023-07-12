<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Transaction;
use App\Models\Wallet;
use Cknow\Money\Money;

interface CreateTransactions
{
    /**
     * Summary of handle
     */
    public function handle(
        Wallet $wallet,
        int $transactionReason,
        Money $amount,
        array $meta,
        ?string $referenceNumber = null
    ): Transaction;
}
