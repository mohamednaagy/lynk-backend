<?php

namespace App\Support\Transactions\Descriptions\Generators;

use App\Support\Transactions\Descriptions\GeneratorBase;
use Bavix\Wallet\Models\Transaction;

class DefaultGenerator extends GeneratorBase
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        return '';
    }
}
