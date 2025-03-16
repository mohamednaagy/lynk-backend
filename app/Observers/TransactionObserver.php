<?php

namespace App\Observers;

use App\Jobs\Transaction\CheckWalletNotificaitonJob;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        // if the transaction is negative, then it's a withdrawal
        if ($transaction->amount->isNegative()) {
            CheckWalletNotificaitonJob::dispatch($transaction->wallet);
            Log::info('increment transaction FOR REFUND');
            FinancingOrder::where('id', $transaction->financing_order_id)->increment('charged_trader_orders_count');
        } elseif ($transaction->amount->isPositive()) {
            $this->clearNotifiedForWalletNotification($transaction->wallet);
            Log::info('decrement transaction FOR REFUND');
            FinancingOrder::where('id', $transaction->financing_order_id)->decrement('charged_trader_orders_count');
        }
    }

    private function clearNotifiedForWalletNotification(Wallet $wallet)
    {
        /** @var Company $company */
        $company = $wallet->holder;
        $company->walletNotification?->markAsNotNotified();
    }
}
