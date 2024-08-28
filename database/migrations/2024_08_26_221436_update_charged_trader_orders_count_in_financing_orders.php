<?php

use App\Models\FinancingOrder;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->boolean('has_problems')->default(false);
        });
        // Process transactions in chunks
        Transaction::chunkById(1000, function ($transactions) {
            foreach ($transactions as $transaction) {
                $this->updateFinancingOrderCount($transaction);
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
        FinancingOrder::query()->update(['charged_trader_orders_count' => 0]);
    }

    private function updateFinancingOrderCount(Transaction $transaction): void
    {
        $financingOrder = FinancingOrder::where('id', $transaction->financing_order_id);
        if ($transaction->amount->isNegative()) {
            $financingOrder->increment('charged_trader_orders_count');
        } elseif ($transaction->amount->isPositive()) {
            if ($financingOrder->first()?->charged_trader_orders_count == 0) {
                $financingOrder->update(['has_problems' => true]);
            } else {
                $financingOrder->decrement('charged_trader_orders_count');
            }
        }
    }
};
