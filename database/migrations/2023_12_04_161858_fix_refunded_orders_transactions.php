<?php

use App\Enums\TransactionReason;
use App\Models\Transaction;
use App\Support\Collections\TransactionCollection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $refundedTransactionsIds = Transaction::selectRaw('JSON_UNQUOTE(JSON_EXTRACT(meta, \'$.refunded_transaction_id\')) as refunded_transaction_id')
            ->reasons([TransactionReason::RefundOrderCreationFee])
            ->pluck('refunded_transaction_id');

        $refundedTransactions = Transaction::whereIn('id', $refundedTransactionsIds)->get();

        Transaction::reasons([TransactionReason::RefundOrderCreationFee])
            ->chunkById(50, function (TransactionCollection $transactions) use ($refundedTransactions) {
                /** @var Transaction $transaction */
                foreach ($transactions as $transaction) {
                    $refundedTransaction = $refundedTransactions->firstWhere('id', $transaction->meta['refunded_transaction_id']);
                    $meta = array_merge($transaction->meta, Arr::only($refundedTransaction->meta, ['order_cost', 'vat_amount']));
                    $transaction->update([
                        'meta' => $meta,
                    ]);
                }
            });
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
};
