<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class DepositByEdaatType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        return __('transaction-description.deposit_by_edaat', [
            'invoice_number' => Arr::get($transaction->meta, 'invoice_number'),
        ], $locale);
    }

    public function process(Wallet $wallet, Money $amount, int $reason, array $meta): Transaction
    {
        return $wallet->deposit($amount, $reason, $meta);
    }
}
