<?php

namespace App\Support\Transactions\Descriptions;

use App\Enums\TransactionReason;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Transactions\Descriptions\Generators\DefaultGenerator;
use Cknow\Money\Money;

class DescriptionManager
{
    private static array $generators = [];

    /**
     * Undocumented function
     *
     * @param  int  $reason
     * @return DefaultGenerator|GeneratorInterface
     */
    protected static function getGenerator(int $reason): DefaultGenerator|GeneratorInterface
    {
        if (isset(self::$generators[$reason])) {
            return self::$generators[$reason];
        }

        $className = 'App\\Support\\Transactions\\Descriptions\\Generators\\'.TransactionReason::getKey($reason).'Type';

        if (! class_exists($className)) {
            $className = DefaultGenerator::class;
        }

        $generator = new $className;

        self::$generators[$reason] = $generator;

        return $generator;
    }

    /**
     * Get transaction description
     *
     * @param  Transaction  $transaction
     * @param  null  $locale
     * @return string
     */
    public static function getDescription(Transaction $transaction, $locale = null): string
    {
        return self::getGenerator($transaction->meta['type'])->generate($transaction, $locale);
    }

    /**
     * Get transaction description
     *
     * @param  Wallet  $wallet
     * @param  Money  $amount
     * @param  int  $reason
     * @param  array  $meta
     * @return string
     */
    public static function handleTransaction(Wallet $wallet, Money $amount, int $reason, array $meta): string
    {
        return self::getGenerator($reason)->handle($wallet, $amount, $reason, $meta);
    }
}
