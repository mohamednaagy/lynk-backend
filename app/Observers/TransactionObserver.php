<?php

namespace App\Observers;

use App\Jobs\Transaction\CheckWalletNotificaitonJob;
use App\Models\Transaction;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        // if the transaction is negative, then it's a withdrawal
        if ($transaction->amount->isNegative()) {
            CheckWalletNotificaitonJob::dispatch($transaction->wallet);
        }
    }
}
