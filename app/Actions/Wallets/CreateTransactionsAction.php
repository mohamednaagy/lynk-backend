<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Transactions\Descriptions\DescriptionManager;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Config;

class CreateTransactionsAction implements CreateTransactions
{
    /**
     * @param  Wallet  $wallet
     * @param  Money  $amount
     * @param  int  $transactionReason
     * @param  array  $meta
     * @return string
     */
    public function handle(
        Wallet $wallet,
        int $transactionReason,
        string $amount,
        array $meta
    ): Transaction {
        return DescriptionManager::handleTransaction($wallet, Money::parseByDecimal($amount, Config::get('app.currency', 'SAR')), $transactionReason, $meta);
    }
}
