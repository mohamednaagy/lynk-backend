<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Support\Transactions\Descriptions\GeneratorBase;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
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

    public function handleTransaction(Wallet $wallet, string $amount, array $meta)
    {
        $wallet->withdrawFloat($amount, $meta);

        return $wallet->balanceFloat;
    }
}
