<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\SetTransactionBalance;
use App\Models\Transaction;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Log;

class SetTransactionBalanceAction implements SetTransactionBalance
{
    /**
     * Set the balance for a single transaction based on previous balance.
     *
     * @param  Transaction  $transaction  The transaction to set balance for
     * @return Transaction The transaction with updated balance
     */
    public function handle(Transaction $transaction)
    {
        try {
            $previousBalance = $this->getPreviousBalance($transaction);

            $newBalance = $transaction->amount;

            if ($previousBalance) {
                $newBalance = $previousBalance->add($transaction->amount);
            }

            $transaction->balance = $newBalance;
            $transaction->saveQuietly();
        } catch (\Throwable $e) {
            Log::error('Failed to set transaction balance', [
                'transaction_id' => $transaction->id,
                'wallet_id' => $transaction->wallet_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

    }

    private function getPreviousBalance(Transaction $transaction): ?Money
    {
        $previousTransaction = Transaction::where('wallet_id', $transaction->wallet_id)
            ->where('id', '<', $transaction->id)
            ->orderByDesc('id')
            ->first();

        return $previousTransaction?->balance;
    }
}
