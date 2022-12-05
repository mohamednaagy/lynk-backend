<?php

namespace App\Support\Transactions\Descriptions;

use App\Models\Transaction;
use App\Models\Wallet;
use Cknow\Money\Money;

abstract class GeneratorBase implements GeneratorInterface
{
    public function generate(Transaction $transaction, $locale = null): string
    {
        return $this->generateMessage($transaction, $locale);
    }

    public function handle(Wallet $wallet, Money $amount, int $reason, array $meta): Transaction
    {
        return $this->handleTransaction($wallet, $amount, $reason, $meta);
    }

    abstract protected function generateMessage(Transaction $transaction, $locale);

    abstract public function handleTransaction(Wallet $wallet, Money $amount, int $reason, array $meta);
}
