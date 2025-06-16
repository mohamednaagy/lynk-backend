<?php

namespace App\Support\Wallets\Transactions;

use App\Enums\TransactionReason;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\DefaultGenerator;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Log;

class TransactionUtil implements TransactionUtilInterface
{
    private array $cachedHandlers = [];

    /**
     * Undocumented function
     */
    public function resolveHandler(int $reason): DefaultGenerator|TransactionTypeHandlerInterface
    {
        if (isset($this->cachedHandlers[$reason])) {
            return $this->cachedHandlers[$reason];
        }

        $className = 'App\\Support\\Wallets\\Transactions\\TransactionTypeHandlers\\'.TransactionReason::getKey($reason).'Type';

        if (! class_exists($className)) {
            $className = DefaultGenerator::class;
        }

        $generator = new $className;

        $this->cachedHandlers[$reason] = $generator;

        return $generator;
    }

    /**
     * Get transaction description
     *
     * @param  null  $locale
     */
    public function getDescription(Transaction $transaction, $locale = null): string
    {
        return $this->resolveHandler($transaction->reason)->generateMessage($transaction, $locale);
    }

    /**
     * Get transaction description
     */
    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $refrenceNumber,
        array $meta
    ): Transaction {
        try {
            Log::info('TransactionUtil::process START', [
                'wallet_id' => $wallet->getKey(),
                'reason' => $reason,
                'reason_name' => TransactionReason::getKey($reason),
                'amount' => $amount->jsonSerialize(),
                'reference_number' => $refrenceNumber,
            ]);

            $handler = $this->resolveHandler($reason);

            Log::info('TransactionUtil::process - Handler resolved', [
                'wallet_id' => $wallet->getKey(),
                'handler_class' => get_class($handler),
                'reason' => $reason,
            ]);

            $startTime = microtime(true);

            $result = $handler->process($wallet, $amount, $reason, $refrenceNumber, $meta);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('TransactionUtil::process SUCCESS', [
                'wallet_id' => $wallet->getKey(),
                'transaction_id' => $result->id,
                'execution_time_ms' => $executionTime,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('TransactionUtil::process FAILED', [
                'wallet_id' => $wallet->getKey(),
                'reason' => $reason,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
