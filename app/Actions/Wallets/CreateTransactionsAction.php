<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Support\Transactions\Descriptions\DescriptionManager;
use Bavix\Wallet\Models\Wallet;

class CreateTransactionsAction implements CreateTransactions
{
    /**
     * @param  Wallet  $wallet
     * @param  int  $transactionReason
     * @param  string  $amount
     * @param  array  $meta
     * @return string
     */
    public function handle(
        Wallet $wallet,
        int $transactionReason,
        string $amount,
        array $meta
    ): string {
        return DescriptionManager::handleTransaction($transactionReason, $wallet, $amount, $meta);
    }
}
