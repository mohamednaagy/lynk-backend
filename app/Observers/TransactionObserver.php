<?php

namespace App\Observers;

use App\Actions\Wallets\SetTransactionBalanceAction;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class TransactionObserver implements ShouldHandleEventsAfterCommit
{
    use Localizable;

    public function created(Transaction $transaction): void
    {
        $this->setTransactionDescription($transaction);

        app(SetTransactionBalanceAction::class)->handle($transaction);

        $financingOrder = $this->getFinancingOrder($transaction);
        $cost_with_vat = $transaction->cost_with_vat;
        $cost_without_vat = $transaction->cost_without_vat;

        if ($transaction->amount->isNegative()) {
            Log::info('increment transaction FOR REFUND financing_order_id => '.$transaction->financing_order_id.' transaction_id => '.$transaction->id.' wallet_id => '.$transaction->wallet_id);
            FinancingOrder::where('id', $transaction->financing_order_id)->increment('charged_trader_orders_count');

            if ($financingOrder) {
                $financingOrder->addToFinancingOrderCosts($cost_with_vat, $cost_without_vat);
            }
        } elseif ($transaction->amount->isPositive()) {
            Log::info('decrement transaction FOR REFUND financing_order_id => '.$transaction->financing_order_id.' transaction_id => '.$transaction->id.' wallet_id => '.$transaction->wallet_id);
            FinancingOrder::where('id', $transaction->financing_order_id)->decrement('charged_trader_orders_count');

            if ($financingOrder) {
                $financingOrder->subtractFromFinancingOrderCosts($cost_with_vat, $cost_without_vat);
            }
        }
    }

    public function getFinancingOrder(Transaction $transaction): ?FinancingOrder
    {
        return FinancingOrder::where('id', $transaction?->financing_order_id)->first();
    }

    public function setTransactionDescription(Transaction $transaction): void
    {
        $locales = config('app.locales');
        $descriptions = [];
        foreach ($locales as $locale) {
            $this->withLocale($locale, function () use ($transaction, &$descriptions) {
                $descriptions[app()->getLocale()] = ! is_null($transaction->reason)
                    ? app(TransactionUtilInterface::class)->getDescription($transaction)
                    : null;
            });
        }

        $transaction->setTranslations('description', $descriptions);
        $transaction->saveQuietly();
    }
}
