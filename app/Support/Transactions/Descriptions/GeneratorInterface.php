<?php

namespace App\Support\Transactions\Descriptions;

use Bavix\Wallet\Models\Transaction;

interface GeneratorInterface
{
    public function generate(Transaction $transaction, $locale): string;
}
