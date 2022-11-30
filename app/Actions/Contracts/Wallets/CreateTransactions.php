<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Wallet;
use Cknow\Money\Money;

interface CreateTransactions
{
    public function handle(Wallet $wallet, Money $amount, int $transactionReason, array $meta): string;
}
