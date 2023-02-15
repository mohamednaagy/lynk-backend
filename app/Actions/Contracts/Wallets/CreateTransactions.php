<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Transaction;
use App\Models\Wallet;
use Cknow\Money\Money;

interface CreateTransactions
{
    /**
     * Summary of handle
     *
     * @param  Wallet  $wallet
     * @param  int  $transactionReason
     * @param  Money  $amount
     * @param  array  $meta
     * @return Transaction
     */
    public function handle(Wallet $wallet, int $transactionReason, Money $amount, array $meta): Transaction;
}
