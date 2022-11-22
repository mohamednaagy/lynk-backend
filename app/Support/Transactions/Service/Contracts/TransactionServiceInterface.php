<?php

namespace App\Support\Transactions\Service\Contracts;

use App\Models\Wallet;

interface TransactionServiceInterface
{
    public function withdraw(Wallet $wallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta);

    public function deposit(Wallet $wallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta);

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta);

    public function getBalance(Wallet $wallet);

    public function checkIfCanDraw(Wallet $wallet, float| int $amount);
}
