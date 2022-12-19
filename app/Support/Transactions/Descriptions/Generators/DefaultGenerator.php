<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Transactions\Descriptions\GeneratorBase;
use Cknow\Money\Money;

class DefaultGenerator extends GeneratorBase
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        return '';
    }

    public function handleTransaction(Wallet $wallet, Money $amount, int $reason, array $meta)
    {
        return $wallet->deposit($amount, $reason, $meta);
    }
}
