<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Transactions\Descriptions\GeneratorBase;
use Cknow\Money\Money;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

class ManualDepositType extends GeneratorBase
{
    protected function generateMessage(Transaction $transaction, $locale): array|string|Translator|Application|null
    {
        return __('transaction-description.manual_deposit');
    }

    public function handleTransaction(Wallet $wallet, Money $amount, int $reason, array $meta): Transaction
    {
        return $wallet->deposit($amount, $reason, $meta);
    }
}
