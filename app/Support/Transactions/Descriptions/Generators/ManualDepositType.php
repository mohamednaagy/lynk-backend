<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Models\Transaction;
use App\Support\Transactions\Descriptions\GeneratorBase;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

class ManualDepositType extends GeneratorBase
{
    protected function generateMessage(Transaction $transaction, $locale): array|string|Translator|Application|null
    {
        return __('transaction-description.manual_deposit');
    }

    public function handleTransaction(Wallet $wallet, string $amount, array $meta): Transaction
    {
        return $wallet->depositFloat($amount, $meta);
    }
}
