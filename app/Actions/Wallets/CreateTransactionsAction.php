<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Log;

class CreateTransactionsAction implements CreateTransactions
{
    public function handle(
        Wallet $wallet,
        int $transactionReason,
        Money $amount,
        array $meta,
        ?string $referenceNumber = null
    ): Transaction {
        try {
            Log::info('start CreateTransactionsAction with wallet_id => '.$wallet->getKey().' and reference_number => '.$referenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'transaction_reason' => $transactionReason,
                'amount' => $amount->jsonSerialize(),
                'reference_number' => $referenceNumber,
                'meta_keys' => array_keys($meta),
            ]);

            $startTime = microtime(true);

            $result = app(TransactionUtilInterface::class)->process(
                $wallet,
                $amount,
                $transactionReason,
                $referenceNumber,
                $meta
            );

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('CreateTransactionsAction::handle SUCCESS with wallet_id => '.$wallet->getKey().' and reference_number => '.$referenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'transaction_id' => $result->id,
                'execution_time_ms' => $executionTime,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('CreateTransactionsAction::handle FAILED with wallet_id => '.$wallet->getKey().' and reference_number => '.$referenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'transaction_reason' => $transactionReason,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
