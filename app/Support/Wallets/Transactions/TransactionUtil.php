<?php

namespace App\Support\Wallets\Transactions;

use App\Enums\TransactionReason;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\DefaultGenerator;
use Cknow\Money\Money;

class TransactionUtil implements TransactionUtilInterface
{
    private array $cachedHandlers = [];

    /**
     * Undocumented function
     */
    public function resolveHandler(int $reason): DefaultGenerator|TransactionTypeHandlerInterface
    {
        if (isset($this->cachedHandlers[$reason])) {
            return $this->cachedHandlers[$reason];
        }

        $className = 'App\\Support\\Wallets\\Transactions\\TransactionTypeHandlers\\'.TransactionReason::getKey($reason).'Type';

        if (! class_exists($className)) {
            $className = DefaultGenerator::class;
        }

        $generator = new $className;

        $this->cachedHandlers[$reason] = $generator;

        return $generator;
    }

    /**
     * Get transaction description
     *
     * @param  null  $locale
     */
    public function getDescription(Transaction $transaction, $locale = null): string
    {
        return $this->resolveHandler($transaction->reason)->generateMessage($transaction, $locale);
    }

    /**
     * Get transaction description
     */
    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $refrenceNumber,
        array $meta
    ): Transaction {
        return $this->resolveHandler($reason)
            ->process($wallet, $amount, $reason, $refrenceNumber, $meta);
    }
}
