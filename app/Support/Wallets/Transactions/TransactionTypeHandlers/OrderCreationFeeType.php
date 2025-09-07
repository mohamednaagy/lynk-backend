<?php

namespace App\Support\Wallets\Transactions\TransactionTypeHandlers;

use App\Models\TraderOrder;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use Cknow\Money\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class OrderCreationFeeType implements TransactionTypeHandlerInterface
{
    public function generateMessage(Transaction $transaction, $locale): string
    {
        $items = Arr::only($transaction->meta, ['type', 'financing_order_id', 'trader_order_id']);
        $traderOrder = isset($items['trader_order_id']) ? TraderOrder::find($items['trader_order_id']) : null;
        $traderReferenceNumber = $traderOrder ? ($traderOrder->reference ? $traderOrder->reference : $traderOrder->id) : '';

        return __('transaction-description.order_creation_fee', [
            'order_number' => $items['financing_order_id'] ?? '',
            'trader_order_reference_number' => $traderReferenceNumber,
        ], $locale);
    }

    public function process(
        Wallet $wallet,
        Money $amount,
        int $reason,
        ?string $referenceNumber,
        array $meta
    ): Transaction {
        try {
            Log::info('OrderCreationFeeType::process START trader_order_id => '.$meta['trader_order_id'] ?? 'unknown'.' wallet_id => '.$wallet->getKey().' reference_number => '.$referenceNumber, [
                'wallet_id' => $wallet->getKey(),
                'amount' => $amount->jsonSerialize(),
                'reason' => $reason,
                'reference_number' => $referenceNumber,
                'trader_order_id' => $meta['trader_order_id'] ?? 'unknown',
            ]);

            $startTime = microtime(true);

            $result = $wallet->withdraw($amount, $reason, $referenceNumber, $meta);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('OrderCreationFeeType::process SUCCESS trader_order_id => '.$meta['trader_order_id'] ?? 'unknown'.' wallet_id => '.$wallet->getKey().' reference_number => '.$referenceNumber.' transaction_id => '.$result->id, [
                'wallet_id' => $wallet->getKey(),
                'transaction_id' => $result->id,
                'execution_time_ms' => $executionTime,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('OrderCreationFeeType::process FAILED trader_order_id => '.$meta['trader_order_id'] ?? 'unknown'.' wallet_id => '.$wallet->getKey().' reference_number => '.$referenceNumber.' transaction_id => '.$result->id, [
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
