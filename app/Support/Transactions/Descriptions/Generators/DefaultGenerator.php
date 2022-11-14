<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Support\Transactions\Descriptions\GeneratorBase;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;

class DefaultGenerator extends GeneratorBase
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        return '';
    }

    public function handleTransaction(Wallet $wallet, string $amount, array $meta)
    {
        return $wallet->deposit($amount, $meta);
    }
}
