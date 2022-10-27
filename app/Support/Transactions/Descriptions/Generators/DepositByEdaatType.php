<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Support\Transactions\Descriptions\GeneratorBase;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

class DepositByEdaatType extends GeneratorBase
{
    protected function generateMessage(Transaction $transaction, $locale): array|string|Translator|Application|null
    {
        return __('transaction-description.top_up', [], $locale);
    }

    public function handleTransaction(Wallet $wallet, string $amount, array $meta)
    {
        $wallet->depositFloat($amount, $meta);

        return $wallet->balanceFloat;
    }
}
