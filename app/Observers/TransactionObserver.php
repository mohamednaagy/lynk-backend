<?php

namespace App\Observers;

use App\Jobs\Transaction\CheckWalletNotificaitonJob;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class TransactionObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Transaction $transaction): void
    {
        $financingOrder = $this->getFinancingOrder($transaction);
        $cost_with_vat = $transaction->cost_with_vat;
        $cost_without_vat = $transaction->cost_without_vat;

        if ($transaction->amount->isNegative()) {
            CheckWalletNotificaitonJob::dispatch($transaction->wallet);
            Log::info('increment transaction FOR REFUND financing_order_id => '.$transaction->financing_order_id.' transaction_id => '.$transaction->id.' wallet_id => '.$transaction->wallet_id);
            FinancingOrder::where('id', $transaction->financing_order_id)->increment('charged_trader_orders_count');

            if ($financingOrder) {
                $financingOrder->addToFinancingOrderCosts($cost_with_vat, $cost_without_vat);
            }

        } elseif ($transaction->amount->isPositive()) {
            $this->clearNotifiedForWalletNotification($transaction->wallet);
            Log::info('decrement transaction FOR REFUND financing_order_id => '.$transaction->financing_order_id.' transaction_id => '.$transaction->id.' wallet_id => '.$transaction->wallet_id);
            FinancingOrder::where('id', $transaction->financing_order_id)->decrement('charged_trader_orders_count');

            if ($financingOrder) {
                $financingOrder->subtractFromFinancingOrderCosts($cost_with_vat, $cost_without_vat);
            }

        }
    }

    private function clearNotifiedForWalletNotification(Wallet $wallet)
    {
        /** @var Company $company */
        $company = $wallet->holder;
        $company->walletNotification?->markAsNotNotified();
    }

    public function getFinancingOrder(Transaction $transaction): ?FinancingOrder
    {
        return FinancingOrder::where('id', $transaction?->financing_order_id)->first();
    }
}
