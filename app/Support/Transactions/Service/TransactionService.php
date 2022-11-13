<?php

namespace App\Support\Transactions\Service;

use App\Models\User;
use App\Models\Wallet;
use App\Support\Transactions\Service\Contracts\TransactionServiceInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;
use Brick\Math\BigDecimal;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(protected ?User $user, protected ?Wallet $wallet)
    {
    }

    public function withdraw(float|int $amount, int $type, ?string $referenceNumber, ?array $meta)
    {
        return Transaction::create([
            'payable_type' => $this->user->getMorphClass(),
            'payable_id' => $this->user->getKey(),
            'wallet_id' => $this->wallet->getKey(),
            'amount' => $amount,
            'type' => $type,
            'reference_number' => $referenceNumber,
            'meta' => $meta,
        ]);
    }

    public function deposit(float|int $amount, int $type, ?string $referenceNumber, ?array $meta)
    {
        return Transaction::create([
            'payable_type' => $this->user->getMorphClass(),
            'payable_id' => $this->user->getKey(),
            'wallet_id' => $this->wallet->getKey(),
            'amount' => $amount,
            'type' => $type,
            'reference_number' => $referenceNumber,
            'meta' => $meta,
        ]);
    }

    public function transfer(Wallet $toWallet, float|int $amount, int $type, ?string $referenceNumber, ?array $meta)
    {
        $withdraw = $this->withdraw($amount, $type, $referenceNumber, $meta);
        $deposit = $this->deposit($amount, $type, $referenceNumber, $meta);

        return Transfer::create([
            'from_type' => $this->user->getMorphClass(),
            'from_id' => $this->user->getKey(),
            'to_type' => $toWallet->getMorphClass(),
            'to_id' => $toWallet->getKey(),
            'deposit_id' => $deposit->getKey(),
            'withdraw_id' => $withdraw->getKey(),
        ]);
    }

    public function getBalance()
    {
        return $this->wallet->getBalanceAttribute();
    }

    public function checkIfCanDraw(float| int $amount)
    {
        $balance = $this->getBalance();

        return BigDecimal::of($balance)->compareTo(BigDecimal::of($amount)) >= 0;
    }
}
