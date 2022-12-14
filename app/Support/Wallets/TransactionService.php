<?php

namespace App\Support\Wallets;

use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterface;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use Brick\Math\BigDecimal;
use Cknow\Money\Money;
use Illuminate\Support\Str;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(protected ReferenceNumberGeneratorInterface $referenceNumberGeneratorInterface)
    {
    }

    public function withdraw(
        Wallet $wallet,
        Money $amount,
        int $type,
        string $referenceNumber = null,
        array $meta = []
    ) {
        return Transaction::create([
            'wallet_id' => $wallet->getKey(),
            'amount' => $amount->isNegative() ? $amount : $amount->negative(),
            'reason' => $type,
            'uuid' => Str::uuid(),
            'reference_number' => $referenceNumber ?? $this->referenceNumberGeneratorInterface->generate(),
            'meta' => $meta,
        ]);
    }

    public function deposit(
        Wallet $wallet,
        Money $amount,
        int $type,
        string $referenceNumber = null,
        array $meta = []
    ) {
        return Transaction::create([
            'wallet_id' => $wallet->getKey(),
            'amount' => $amount,
            'reason' => $type,
            'uuid' => Str::uuid(),
            'reference_number' => $referenceNumber ?? $this->referenceNumberGeneratorInterface->generate(),
            'meta' => $meta,
        ]);
    }

    public function transfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        Money $amount,
        int $type,
        string $referenceNumber = null,
        array $meta = []
    ) {
        $withdraw = $this->withdraw($fromWallet, $amount, $type, $referenceNumber, $meta);
        $deposit = $this->deposit($toWallet, $amount, $type, $referenceNumber, $meta);

        return Transfer::create([
            'uuid' => Str::uuid(),
            'amount' => $amount,
            'from_id' => $fromWallet->getKey(),
            'to_id' => $toWallet->getKey(),
            'deposit_id' => $deposit->getKey(),
            'withdraw_id' => $withdraw->getKey(),
            'data' => $meta,
        ]);
    }

    public function getBalance(Wallet $wallet): Money
    {
        return $wallet->balance;
    }

    public function checkIfCanDraw(Wallet $wallet, Money $amount)
    {
        $balance = $this->getBalance($wallet);

        return BigDecimal::of($balance)->compareTo(BigDecimal::of($amount)) >= 0;
    }
}
