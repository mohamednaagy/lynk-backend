<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Transactions\Descriptions\GeneratorBase;
use Cknow\Money\Money;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;

class OrderCreationFeeType extends GeneratorBase
{
    protected function generateMessage(Transaction $transaction, $locale): array|string|Translator|Application|null
    {
        $items = Arr::only($transaction->meta, ['type', 'order_number']);

        return __('transaction-description.order_creation_fee', [
            'order_number' => $items['order_number'] ?? '',
        ], $locale);
    }

    public function handleTransaction(Wallet $wallet, Money $amount, int $reason, array $meta)
    {
        return $wallet->withdraw($amount, $reason, $meta);
    }
}
