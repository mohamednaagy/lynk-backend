<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class OrderCreationFeeType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        $items = Arr::only($transaction->meta, ['type', 'financing_order_id']);

        return __('transaction-description.order_creation_fee', [
            'order_number' => $items['financing_order_id'] ?? '',
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
