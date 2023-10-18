<?php

namespace App\Observers;

use App\Jobs\Transaction\CheckWalletNotificaitonJob;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        // if the transaction is negative, then it's a withdrawal
        if ($transaction->amount->isNegative()) {
            CheckWalletNotificaitonJob::dispatch($transaction->wallet);
        } elseif ($transaction->amount->isPositive()) {
            $this->clearNotifiedForWalletNotification($transaction->wallet);
        }
    }

    private function clearNotifiedForWalletNotification(Wallet $wallet)
    {
        /** @var Company $company */
        $company = $wallet->holder;
        $company->walletNotification?->markAsNotNotified();
    }
}
