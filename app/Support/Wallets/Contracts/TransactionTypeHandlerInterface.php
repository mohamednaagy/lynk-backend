<?php

namespace App\Support\Wallets\Contracts;

use App\Models\Transaction;
use App\Models\Wallet;
use Cknow\Money\Money;

interface TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string;

    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $referenceNumber,
        array $meta
    ): Transaction;
}
