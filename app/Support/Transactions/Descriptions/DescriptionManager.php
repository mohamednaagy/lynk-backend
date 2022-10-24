<?php

namespace App\Support\Transactions\Descriptions;

use App\Support\Transactions\Descriptions\Generators\DefaultGenerator;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Arr;

class DescriptionManager
{
    /**
     * Undocumented function
     *
     * @param  Transaction  $transaction
     * @return DefaultGenerator|GeneratorInterface
     */
    protected static function getGenerator(Transaction $transaction): DefaultGenerator|GeneratorInterface
    {
        $className = DefaultGenerator::class;

        if ($class = Arr::get($transaction->meta, 'description')) {
            $className = 'App\\Support\\Transactions\\Descriptions\\Generators\\'.$class.'Type';
        }

        if (! class_exists($className)) {
            $className = DefaultGenerator::class;
        }

        return new $className;
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
        return self::getGenerator($transaction)->generate($transaction, $locale);
    }
}
