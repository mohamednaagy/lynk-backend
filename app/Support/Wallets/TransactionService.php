<?php

namespace App\Support\Wallets;

use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterface;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use Brick\Math\BigDecimal;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(protected ReferenceNumberGeneratorInterface $referenceNumberGeneratorInterface) {}

    public function withdraw(
        Wallet $wallet,
        Money $amount,
        int $type,
        ?string $referenceNumber = null,
        array $meta = []
    ) {
        try {
            Log::info('TransactionService::withdraw START wallet_id => '.$wallet->getKey().' reference_number => '.$referenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'wallet_currency' => $wallet->currency,
                'amount' => $amount->jsonSerialize(),
                'type' => $type,
                'reference_number' => $referenceNumber,
                'meta_keys' => array_keys($meta),
            ]);

            // Generate reference number if not provided
            $finalReferenceNumber = $referenceNumber ?? $this->referenceNumberGeneratorInterface->generate();

            Log::info('TransactionService::withdraw - Reference number generated wallet_id => '.$wallet->getKey().' reference_number => '.$finalReferenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'reference_number' => $finalReferenceNumber,
                'was_generated' => $referenceNumber === null,
            ]);

            // Prepare transaction data
            $transactionData = [
                'wallet_id' => $wallet->getKey(),
                'amount' => $amount->isNegative() ? $amount : $amount->negative(),
                'reason' => $type,
                'reference_number' => $finalReferenceNumber,
                'meta' => $meta,
            ];

            Log::info('TransactionService::withdraw - About to create transaction wallet_id => '.$wallet->getKey().' reference_number => '.$finalReferenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'transaction_data' => [
                    'wallet_id' => $transactionData['wallet_id'],
                    'amount' => $transactionData['amount']->jsonSerialize(),
                    'reason' => $transactionData['reason'],
                    'reference_number' => $transactionData['reference_number'],
                    'meta_size' => count($transactionData['meta']),
                ],
            ]);

            $startTime = microtime(true);

            $transaction = Transaction::create($transactionData);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('TransactionService::withdraw SUCCESS wallet_id => '.$wallet->getKey().' reference_number => '.$finalReferenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'transaction_id' => $transaction->id,
                'transaction_amount' => $transaction->amount->jsonSerialize(),
                'execution_time_ms' => $executionTime,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            Log::error('TransactionService::withdraw FAILED wallet_id => '.$wallet->getKey().' reference_number => '.$finalReferenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function deposit(
        Wallet $wallet,
        Money $amount,
        int $type,
        ?string $referenceNumber = null,
        array $meta = []
    ) {
        return Transaction::create([
            'wallet_id' => $wallet->getKey(),
            'amount' => $amount->isPositive() ? $amount : $amount->absolute(),
            'reason' => $type,
            'reference_number' => $referenceNumber ?? $this->referenceNumberGeneratorInterface->generate(),
            'meta' => $meta,
        ]);
    }

    public function transfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        Money $amount,
        int $type,
        ?string $referenceNumber = null,
        array $meta = []
    ) {
        $withdraw = $this->withdraw($fromWallet, $amount, $type, $referenceNumber, $meta);
        $deposit = $this->deposit($toWallet, $amount, $type, $referenceNumber, $meta);

        return Transfer::create([
            'from_id' => $fromWallet->getKey(),
            'to_id' => $toWallet->getKey(),
            'deposit_id' => $deposit->getKey(),
            'withdraw_id' => $withdraw->getKey(),
            'amount' => $amount->isPositive() ? $amount : $amount->absolute(),
            'currency' => $amount->getCurrency(),
            'uuid' => Str::uuid(),
            'meta' => $meta,
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
