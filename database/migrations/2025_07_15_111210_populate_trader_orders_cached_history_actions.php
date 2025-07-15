<?php

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

        $affectedRows = DB::update('
            UPDATE trader_orders 
            INNER JOIN (
                SELECT 
                    trader_order_id,
                    action as last_action
                FROM trader_histories th1
                WHERE th1.id = (
                    SELECT MAX(th2.id) 
                    FROM trader_histories th2 
                    WHERE th2.trader_order_id = th1.trader_order_id
                )
            ) latest_histories ON trader_orders.id = latest_histories.trader_order_id
            SET 
                trader_orders.last_history_action = latest_histories.last_action,
                trader_orders.last_history_action_updated_at = NOW()
            WHERE trader_orders.last_history_action IS NULL
        ');

        Log::info("Updated {$affectedRows} trader orders with cached history actions");
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
