<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class VatPercentageFeeType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        $items = Arr::only($transaction->meta, ['transaction_id', 'financing_order_id', 'vat_rate']);

        return __('transaction-description.vat_percentage', [
            'order_id' => $items['financing_order_id'] ?? '',
            'vat_percentage' => ($items['vat_rate'] ?? 0) * 100,
        ], $locale);
    }

    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $referenceNumber,
        array $meta
    ): Transaction {
        return $wallet->withdraw($amount, $reason, $referenceNumber, $meta);
    }
}
