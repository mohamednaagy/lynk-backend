<?php

namespace App\Support\Transactions\Service;

use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterface;
use App\Support\Transactions\Service\Contracts\TransactionServiceInterface;
use Brick\Math\BigDecimal;
use Illuminate\Support\Str;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(protected ReferenceNumberGeneratorInterface $referenceNumberGeneratorInterface)
    {
    }

    public function withdraw(Wallet $wallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta)
    {
        return Transaction::create([
            'wallet_id' => $wallet->getKey(),
            'amount' => $amount,
            'type' => $type,
            'uuid' => Str::uuid(),
            'reference_number' => $referenceNumber ?? $this->referenceNumberGeneratorInterface->generate(),
            'meta' => $meta,
        ]);
    }

    public function deposit(Wallet $wallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta)
    {
        return Transaction::create([
            'wallet_id' => $wallet->getKey(),
            'amount' => $amount,
            'type' => $type,
            'uuid' => Str::uuid(),
            'reference_number' => $referenceNumber ?? $this->referenceNumberGeneratorInterface->generate(),
            'meta' => $meta,
        ]);
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta)
    {
        $withdraw = $this->withdraw($fromWallet, $amount, $type, $referenceNumber, $meta);
        $deposit = $this->deposit($toWallet, $amount, $type, $referenceNumber, $meta);

        return Transfer::create([
            'from_type' => $fromWallet->getMorphClass(),
            'from_id' => $fromWallet->getKey(),
            'to_type' => $toWallet->getMorphClass(),
            'to_id' => $toWallet->getKey(),
            'deposit_id' => $deposit->getKey(),
            'withdraw_id' => $withdraw->getKey(),
            'meta' => $meta,
        ]);
    }

    public function getBalance(Wallet $wallet)
    {
        return $wallet->balance;
    }

    public function checkIfCanDraw(Wallet $wallet, float| int $amount)
    {
        $balance = $this->getBalance($wallet);

        return BigDecimal::of($balance)->compareTo(BigDecimal::of($amount)) >= 0;
    }
}
