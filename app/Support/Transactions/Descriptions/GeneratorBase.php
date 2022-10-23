<?php

namespace App\Support\Transactions\Descriptions;

use Bavix\Wallet\Models\Transaction;

abstract class GeneratorBase implements GeneratorInterface
{
    public function generate(Transaction $transaction, $locale = null): string
    {
        return $this->generateMessage($transaction, $locale);
    }

    abstract protected function generateMessage(Transaction $transaction, $locale);
}
