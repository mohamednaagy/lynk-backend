<?php

use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private const BATCH_SIZE = 1000;

    private const SLEEP_MICROSECONDS = 100000; // 0.1 second delay

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $traderOrdersOldCount = TraderOrder::whereNull('last_history_action')->count();
        $totalAffectedRows = 0;

        TraderOrder::whereNull('last_history_action')->chunkById(self::BATCH_SIZE, function ($traderOrders) use (&$totalAffectedRows) {
            foreach ($traderOrders as $traderOrder) {
                $traderHistory = $traderOrder->traderHistories()->latest('id')->first();
                if ($traderHistory) {
                    $traderOrder->updateLastHistoryAction($traderHistory);
                } else {
                    DB::table('trader_orders')->where('id', $traderOrder->id)->update([
                        'last_history_action' => FinancingOrderHistory::GetTtiId,
                        'last_history_action_updated_at' => now(),
                    ]);
                }
                $totalAffectedRows++;
            }
        });

        if ($traderOrdersOldCount !== $totalAffectedRows) {
            Log::error('Trader orders count mismatch after migration', [
                'traderOrdersOldCount' => $traderOrdersOldCount,
                'totalAffectedRows' => $totalAffectedRows,
            ]);
            throw new \Exception('Trader orders count mismatch after migration');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        TraderOrder::whereNotNull('last_history_action')->chunkById(self::BATCH_SIZE, function ($traderOrders) {
            DB::table('trader_orders')->whereIn('id', $traderOrders->pluck('id'))->update([
                'last_history_action' => null,
                'last_history_action_updated_at' => null,
            ]);
        });
    }
};
