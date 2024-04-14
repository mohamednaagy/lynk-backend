<?php

use App\Enums\TransactionReason;
use App\Models\TraderOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $transactions = $this->getUnhandledTransactions();
        foreach ($transactions as $transaction) {
            try {
                if ($this->isValidTransactionToHandle($transaction)) {
                    $this->handleTransactionMeta($transaction);
                }
            } catch (\Exception $e) {
                Log::channel('transactionUpdates')->info('Error While Update Meta Data For Transaction ', [
                    'transaction' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    /**
     * Checks if the transaction is valid to handle.
     *
     * @param  \App\Models\Transaction  $transaction  The transaction to be validated.
     * @return bool True if the transaction is valid, false otherwise.
     */
    private function isValidTransactionToHandle($transaction)
    {
        // there is no financing_order_id, so we can't append other attributes
        if (! isset($transaction->meta['financing_order_id'])) {
            Log::channel('transactionUpdates')->info('Financing Order Attribute In Meta Json Doesnt Exist ', [
                'transaction_id' => $transaction->id,
            ]);

            return false;
        }

        $relatedTradeOrders = TraderOrder::where('financing_order_id', $transaction->meta['financing_order_id'])->get();

        // transaction Should be related to only one trader order
        // if there is multiple orders, other attributes should append manually
        // if there is no related order, we can ignore this transaction
        if ($relatedTradeOrders->count() != 1) {
            Log::channel('transactionUpdates')->info('Transaction related To Unexpected number of Trade Order ', [
                'transaction_id' => $transaction->id,
                'Financing Order id' => $transaction->meta['financing_order_id'],
                'Trade orders count' => $relatedTradeOrders->count(),
                'Trade Orders Id' => $relatedTradeOrders->pluck('id')->toArray(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Get a list of transactions that have not been handled yet
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getUnhandledTransactions()
    {
        return \App\Models\Transaction::where(function ($query) {
            $query->where('reason', TransactionReason::OrderCreationFee)
                ->orWhere('reason', TransactionReason::RefundOrderCreationFee);
        })
            ->whereJsonDoesntContain('meta', 'trader_order_id')
            ->get();
    }

    /**
     * Handles the transaction meta by updating it with the related trader order's data.
     *
     * @param  \App\Models\Transaction  $transaction  The transaction to be updated.
     */
    private function handleTransactionMeta($transaction)
    {
        // Get the related trader orders based on the financing_order_id in the transaction's meta.
        $relatedTradeOrders = TraderOrder::where('financing_order_id', $transaction->meta['financing_order_id'])->get();

        // Get the first related trader order.
        $relatedTradeOrder = $relatedTradeOrders->first();

        // Update the transaction's meta with the extracted missed data from the related trader order.
        $this->updateTransactionMeta($transaction, $this->extractMissedData($relatedTradeOrder));
    }

    private function extractMissedData($orderTrade)
    {

        $data['trader_order_id'] = $orderTrade->id;

        return $data;
    }

    private function updateTransactionMeta($transaction, $updatedData)
    {
        $updatedMeta = $transaction->meta;

        $updatedMeta['trader_order_id'] = $updatedData['trader_order_id'];

        $transaction->update([
            'meta' => $updatedMeta,
        ]);

        Log::channel('transactionUpdates')->info('Success Update Meta Data For Transaction ', [
            'transaction_id' => $transaction->id,
            'updatedData' => $updatedData,
        ]);
    }
};
