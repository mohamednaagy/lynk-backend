<?php

namespace App\Support\Transactions\Service\Contracts;

use App\Models\Wallet;

interface TransactionServiceInterface
{
    public function withdraw(float|int $amount, int $type, ?string $referenceNumber, ?array $meta);

    public function deposit(float|int $amount, int $type, ?string $referenceNumber, ?array $meta);

    public function transfer(Wallet $toWallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta);

    public function getBalance();

    public function checkIfCanDraw(float|int $amount);
}
