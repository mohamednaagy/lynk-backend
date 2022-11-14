<?php

namespace App\Support\Transactions\Descriptions;

use App\Models\Transaction;
use Bavix\Wallet\Models\Wallet;

abstract class GeneratorBase implements GeneratorInterface
{
    public function generate(Transaction $transaction, $locale = null): string
    {
        return $this->generateMessage($transaction, $locale);
    }

    public function handle(Wallet $wallet, string $amount, array $meta): Transaction
    {
        return $this->handleTransaction($wallet, $amount, $meta);
    }

    abstract protected function generateMessage(Transaction $transaction, $locale);

    abstract public function handleTransaction(Wallet $wallet, string $amount, array $meta);
}
