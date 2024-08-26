<?php

use App\Models\FinancingOrder;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();

        try {
            // Process transactions in chunks
            Transaction::chunkById(1000, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $this->updateFinancingOrderCount($transaction);
                }
            });
            DB::commit(); 
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack(); 
            throw $e; 
        }
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
        if ($transaction->amount->isNegative()) {
            FinancingOrder::where('id', $transaction->financing_order_id)
                ->increment('charged_trader_orders_count');
        } elseif ($transaction->amount->isPositive()) {
            FinancingOrder::where('id', $transaction->financing_order_id)
                ->decrement('charged_trader_orders_count');
        }
    }
};
