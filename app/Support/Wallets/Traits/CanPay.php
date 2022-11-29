<?php

namespace App\Support\Wallets\Traits;

use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use Cknow\Money\Money;

trait CanPay
{
    public function withdraw(Money $amount, int $type, ...$parameters)
    {
        [$referenceNumber, $meta] = $this->resolveReferenceNumberAndMeta($parameters);

        return app(TransactionServiceInterface::class)
            ->withdraw($this, $amount, $type, $referenceNumber, $meta);
    }

    public function deposit(Money $amount, int $type, ...$parameters)
    {
        [$referenceNumber, $meta] = $this->resolveReferenceNumberAndMeta($parameters);

        return app(TransactionServiceInterface::class)
            ->deposit($this, $amount, $type, $referenceNumber, $meta);
    }

    public function transfer(Wallet $toWallet, Money $amount, int $type, ...$parameters)
    {
        [$referenceNumber, $meta] = $this->resolveReferenceNumberAndMeta($parameters);

        return app(TransactionServiceInterface::class)
            ->transfer($this, $toWallet, $amount, $type, $referenceNumber, $meta);
    }

    protected function resolveReferenceNumberAndMeta($parameters)
    {
        if (count($parameters) > 2) {
            throw new \ArgumentCountError('Passing a lot of arguments. Expecting two arguments or less');
        }

        if (count($parameters) === 2) {
            return $parameters;
        }

        // $parameters count is 1 and it is string,
        // that means that it is $refrenceNumber
        if (is_string($parameters[0])) {
            return [$parameters[0], []];
        }

        // Otherwise it is array which represents $meta
        if (is_array($parameters[0])) {
            return [null, $parameters[0]];
        }

        throw new \InvalidArgumentException(
            'Provided meta/reference number don\'t match their respective types [string|null,array]'
        );
    }
}
