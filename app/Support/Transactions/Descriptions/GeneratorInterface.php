<?php

namespace App\Support\Transactions\Descriptions;

use App\Models\Transaction;

interface GeneratorInterface
{
    public function generate(Transaction $transaction, $locale): string;
}
