<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Transaction;

interface SetTransactionBalance
{
    /**
     * Set the balance for a single transaction based on previous balance.
     *
     * @param  Transaction  $transaction  The transaction to set balance for
     * @return Transaction The transaction with updated balance
     */
    public function handle(Transaction $transaction);
}
