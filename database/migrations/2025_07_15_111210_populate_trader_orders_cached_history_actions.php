<?php

use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use Illuminate\Database\Migrations\Migration;
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

        TraderOrder::whereNull('last_history_action')->chunk(self::BATCH_SIZE, function ($traderOrders) use (&$totalAffectedRows) {
            foreach ($traderOrders as $traderOrder) {
                $traderHistoriesCount = $traderOrder->traderHistories()->count();
                if ($traderHistoriesCount > 0) {
                    $traderOrder->updateLastHistoryAction($traderOrder->traderHistories()->latest()->first());
                } else {
                    $traderOrder->update([
                        'last_history_action' => TraderOrderStatus::Initiated,
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
        TraderOrder::whereNotNull('last_history_action')->chunk(self::BATCH_SIZE, function ($traderOrders) {
            TraderOrder::whereIn('id', $traderOrders->pluck('id'))->update([
                'last_history_action' => null,
                'last_history_action_updated_at' => null,
            ]);
        });
    }
};
