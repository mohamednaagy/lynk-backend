<?php

namespace App\Support\Wallets\Contracts;

use App\Models\Wallet;
use Cknow\Money\Money;

interface TransactionServiceInterface
{
    public function withdraw(
        Wallet $wallet,
        Money $amount,
        int $type,
        string $referenceNumber,
        array $meta
    );

    public function deposit(
        Wallet $wallet,
        Money $amount,
        int $type,
        string $referenceNumber,
        array $meta
    );

    public function transfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        Money $amount,
        int $type,
        string $referenceNumber,
        array $meta
    );

    public function getBalance(Wallet $wallet);

    public function checkIfCanDraw(Wallet $wallet, Money $amount);
}
