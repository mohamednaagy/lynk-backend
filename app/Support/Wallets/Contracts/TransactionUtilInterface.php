<?php

namespace App\Support\Wallets\Contracts;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\DefaultGenerator;
use Cknow\Money\Money;

interface TransactionUtilInterface
{
    public function getDescription(Transaction $transaction, $locale = null): string;

    public function resolveHandler(int $reason): DefaultGenerator|TransactionTypeHandlerInterface;

    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $referenceNumber,
        array $meta
    ): Transaction;
}
