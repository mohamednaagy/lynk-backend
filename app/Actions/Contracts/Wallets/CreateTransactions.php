<?php

namespace App\Actions\Contracts\Wallets;

use Bavix\Wallet\Models\Wallet;

interface CreateTransactions
{
    /**
     * @param  Wallet  $wallet
     * @param  int  $transactionReason
     * @param  string  $amount
     * @param  array  $meta
     * @return string
     */
    public function handle(Wallet $wallet, int $transactionReason, string $amount, array $meta): string;
}
