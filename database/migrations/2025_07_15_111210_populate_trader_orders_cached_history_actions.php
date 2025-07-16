<?php

use App\Models\TraderOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Starting population of cached history actions for trader orders');
        $traderOrdersOldCount = TraderOrder::whereNull('last_history_action')->count();
        $affectedRows = DB::update('
            UPDATE trader_orders 
            INNER JOIN (
                SELECT 
                    trader_order_id,
                    action as last_action,
                    created_at as last_action_updated_at
                FROM trader_histories th1
                WHERE th1.id = (
                    SELECT MAX(th2.id) 
                    FROM trader_histories th2 
                    WHERE th2.trader_order_id = th1.trader_order_id
                )
            ) latest_histories ON trader_orders.id = latest_histories.trader_order_id
            SET 
                trader_orders.last_history_action = latest_histories.last_action,
                trader_orders.last_history_action_updated_at = latest_histories.last_action_updated_at
            WHERE trader_orders.last_history_action IS NULL
        ');
        $traderOrdersNewCount = TraderOrder::whereNotNull('last_history_action')->count();
        Log::info('Updated trader orders with cached history actions', [
            'affectedRows' => $affectedRows,
            'traderOrdersOldCount' => $traderOrdersOldCount,
            'traderOrdersNewCount' => $traderOrdersNewCount,
        ]);
        if ($traderOrdersOldCount !== $affectedRows) {
            Log::error('Trader orders count mismatch after migration');
            throw new \Exception('Trader orders count mismatch after migration');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the cached values
        DB::table('trader_orders')->update([
            'last_history_action' => null,
            'last_history_action_updated_at' => null,
        ]);
    }
};
