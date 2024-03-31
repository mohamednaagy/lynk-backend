<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class RefundOrderCreationFeeType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        $items = Arr::only($transaction->meta, ['type', 'financing_order_id', 'trader_order_id']);

        return __('transaction-description.refund_order_creation_fee', [
            'order_number' => $items['financing_order_id'] ?? '',
            'trader_order_number' => $items['trader_order_id'] ?? '',
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
