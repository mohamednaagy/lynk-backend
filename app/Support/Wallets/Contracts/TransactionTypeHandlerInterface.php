<?php

namespace App\Support\Wallets\Contracts;

use App\Models\Transaction;
use App\Models\Wallet;
use Cknow\Money\Money;

interface TransactionTypeHandlerInterface
{
    /**
     * @param  Transaction  $transaction
     * @param $locale
     * @return string
     */
    public function generateMessage(Transaction $transaction, $locale): string;

    /**
     * @param  Wallet  $wallet
     * @param  Money  $amount
     * @param  int  $reason
     * @param  array  $meta
     * @return Transaction
     */
    public function process(Wallet $wallet, Money $amount, int $reason, array $meta): Transaction;
}
