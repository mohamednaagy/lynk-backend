<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Cknow\Money\Money;

class CreateTransactionsAction implements CreateTransactions
{
    public function handle(
        Wallet $wallet,
        int $transactionReason,
        Money $amount,
        array $meta,
        string $referenceNumber = null
    ): Transaction {
        return app(TransactionUtilInterface::class)->process(
            $wallet,
            $amount,
            $transactionReason,
            $referenceNumber,
            $meta
        );
    }
}
