<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Transactions\Descriptions\GeneratorBase;
use Cknow\Money\Money;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;

class VatPercentageFeeType extends GeneratorBase
{
    protected function generateMessage(Transaction $transaction, $locale): array|string|Translator|Application|null
    {
        $items = Arr::only($transaction->meta, ['transaction_id', 'financing_order_id', 'vat_rate']);

        return __('transaction-description.vat_percentage', [
            'order_id' => $items['financing_order_id'] ?? '',
            'vat_percentage' => ($items['vat_rate'] ?? 0) * 100,
        ], $locale);
    }

    public function handleTransaction(Wallet $wallet, Money $amount, int $reason, array $meta)
    {
        return $wallet->withdraw($amount, $reason, $meta);
    }
}
