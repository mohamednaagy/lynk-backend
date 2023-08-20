<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class VatPercentageOnDepositType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        $items = Arr::only($transaction->meta, ['vat_percentage']);

        return __('transaction-description.vat_percentage_recharge', [
            'vat_percentage' => $items['vat_percentage'],
        ], $locale);
    }

    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $referenceNumber,
        array $meta
    ): Transaction {
        return $wallet->deposit($amount, $reason, $referenceNumber, $meta);
    }
}
