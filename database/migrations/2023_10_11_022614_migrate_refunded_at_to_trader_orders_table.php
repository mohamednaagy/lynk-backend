<?php

use App\Enums\TransactionReason;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Transaction::select([
            'meta->trader_order_id as trader_order_id',
            'created_at',
        ])
            ->where('reason', TransactionReason::RefundOrderCreationFee)
            ->chunk(100, function (Collection $transactions) {
                foreach ($transactions as $transaction) {
                    TraderOrder::where('id', $transaction->trader_order_id)->update(['refunded_at' => $transaction->created_at]);
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
