<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;

class ManualDepositType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        return __('transaction-description.manual_deposit');
    }

    public function process(Wallet $wallet, Money $amount, int $reason, array $meta): Transaction
    {
        return $wallet->deposit($amount, $reason, $meta);
    }
}
