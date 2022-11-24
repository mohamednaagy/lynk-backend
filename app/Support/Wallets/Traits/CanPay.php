<?php

namespace App\Support\Wallets\Traits;

use App\Models\Wallet;
use App\Support\Transactions\Service\Contracts\TransactionServiceInterface;
use App\Support\Wallets\InvalidArgument;

trait CanPay
{
    public function withdraw(float|int $amount, int $type, ...$parameters)
    {
        $wallet = $this->wallet; // to change getting wallet from interface or another trait
        $referenceNumber = $this->getDynamicParameters(false, $parameters);
        $meta = $this->getDynamicParameters(true, $parameters);

        return app(TransactionServiceInterface::class)->withdraw($wallet, $amount, $type, $referenceNumber, $meta);
    }

    public function deposit(float|int $amount, int $type, ...$parameters)
    {
        $wallet = $this->wallet; // to change getting wallet from interface or another trait
        $referenceNumber = $this->getDynamicParameters(false, $parameters);
        $meta = $this->getDynamicParameters(true, $parameters);

        return app(TransactionServiceInterface::class)->deposit($wallet, $amount, $type, $referenceNumber, $meta);
    }

    public function transfer(Wallet $toWallet, float|int $amount, int $type, ...$parameters)
    {
        $fromWallet = $this->wallet; // to change getting wallet from interface or another trait
        $referenceNumber = $this->getDynamicParameters(false, $parameters);
        $meta = $this->getDynamicParameters(true, $parameters);

        return app(TransactionServiceInterface::class)->transfer($fromWallet, $toWallet, $amount, $type, $referenceNumber, $meta);
    }

    private function getDynamicParameters(bool $isMeta, $parameters)
    {
        if (count($parameters) > 2) {
            throw new InvalidArgument();
        }

        if ($isMeta) {
            foreach ($parameters as $parameter) {
                if (is_array($parameter)) {
                    return $parameter;
                }
            }

            return [];
        }

        foreach ($parameters  as $parameter) {
            if (! is_array($parameter)) {
                return $parameter;
            }
        }
    }
}
