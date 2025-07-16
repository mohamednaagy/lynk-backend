<?php

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
        Log::info('Starting population of cached history actions for trader orders');

        $ordersToProcess = $this->getOrdersToProcess();

        $this->processOrdersWithHistory();
        $this->processOrdersWithoutHistory();

        $this->validateMigration($ordersToProcess);
    }

    /**
     * Get count of orders that need processing
     */
    private function getOrdersToProcess(): int
    {
        $count = TraderOrder::whereNull('last_history_action')->count();

        Log::info('Processing trader orders in batches', [
            'total_orders_to_process' => $count,
            'batch_size' => self::BATCH_SIZE,
        ]);

        return $count;
    }

    /**
     * Process orders that have history records
     */
    private function processOrdersWithHistory(): void
    {
        $processedCount = 0;

        do {
            // Get batch of order IDs that need processing and have history
            $orderIds = DB::select('
                SELECT DISTINCT trader_orders.id
                FROM trader_orders
                INNER JOIN trader_histories ON trader_orders.id = trader_histories.trader_order_id
                WHERE trader_orders.last_history_action IS NULL
                LIMIT ?
            ', [self::BATCH_SIZE]);

            if (empty($orderIds)) {
                break;
            }

            $ids = collect($orderIds)->pluck('id')->toArray();
            $affectedRows = 0;

            // Process each order individually to get its latest history
            foreach ($ids as $orderId) {
                $latestHistory = DB::selectOne('
                    SELECT action, created_at
                    FROM trader_histories
                    WHERE trader_order_id = ?
                    ORDER BY id DESC
                    LIMIT 1
                ', [$orderId]);

                DB::update('
                        UPDATE trader_orders 
                        SET 
                            last_history_action = ?,
                            last_history_action_updated_at = ?
                        WHERE id = ?
                    ', [
                    $latestHistory->action,
                    $latestHistory->created_at,
                    $orderId,
                ]);
                $affectedRows++;
            }

            $processedCount += $affectedRows;

            Log::info('Processed batch with history records', [
                'batch_affected_rows' => $affectedRows,
                'total_processed' => $processedCount,
            ]);

            if ($affectedRows > 0) {
                usleep(self::SLEEP_MICROSECONDS);
            }
        } while (! empty($orderIds));
    }

    /**
     * Process orders that have no history records
     */
    private function processOrdersWithoutHistory(): void
    {
        $processedCount = 0;

        do {
            // Get order IDs that still need processing (no history)
            $orderIds = DB::select('
                SELECT trader_orders.id 
                FROM trader_orders 
                LEFT JOIN trader_histories ON trader_orders.id = trader_histories.trader_order_id
                WHERE trader_orders.last_history_action IS NULL 
                AND trader_histories.trader_order_id IS NULL
                LIMIT ?
            ', [self::BATCH_SIZE]);

            if (empty($orderIds)) {
                break;
            }

            $ids = collect($orderIds)->pluck('id')->toArray();

            if (! empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $affectedRows = DB::update('
                    UPDATE trader_orders
                    SET 
                        last_history_action = 0,
                        last_history_action_updated_at = NOW()
                    WHERE id IN ('.$placeholders.')
                ', $ids);
            } else {
                $affectedRows = 0;
            }

            $processedCount += $affectedRows;

            Log::info('Processed batch without history records', [
                'batch_affected_rows' => $affectedRows,
                'total_processed' => $processedCount,
            ]);

            if ($affectedRows > 0) {
                usleep(self::SLEEP_MICROSECONDS);
            }
        } while (! empty($orderIds));
    }

    /**
     * Validate the migration completed successfully
     */
    private function validateMigration(int $expectedCount): void
    {
        $processedCount = TraderOrder::whereNotNull('last_history_action')->count();

        Log::info('Completed migration with cached history actions', [
            'trader_orders_old_count' => $expectedCount,
            'trader_orders_new_count' => $processedCount,
        ]);

        if ($expectedCount !== $processedCount) {
            Log::error('Trader orders count mismatch after migration', [
                'expected' => $expectedCount,
                'actual' => $processedCount,
            ]);
            throw new \Exception('Trader orders count mismatch after migration');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Starting rollback of cached history actions');

        $totalAffectedRows = 0;

        do {
            // Get batch of order IDs to rollback
            $orderIds = DB::select('
                SELECT id 
                FROM trader_orders 
                WHERE last_history_action IS NOT NULL 
                LIMIT ?
            ', [self::BATCH_SIZE]);

            if (empty($orderIds)) {
                break;
            }

            $ids = collect($orderIds)->pluck('id')->toArray();

            if (! empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $affectedRows = DB::update('
                    UPDATE trader_orders 
                    SET 
                        last_history_action = NULL,
                        last_history_action_updated_at = NULL
                    WHERE id IN ('.$placeholders.')
                ', $ids);
            } else {
                $affectedRows = 0;
            }

            $totalAffectedRows += $affectedRows;

            Log::info('Rolled back batch', [
                'batch_affected_rows' => $affectedRows,
                'total_rolled_back' => $totalAffectedRows,
            ]);

            if ($affectedRows > 0) {
                usleep(self::SLEEP_MICROSECONDS);
            }
        } while (! empty($orderIds));

        Log::info('Completed rollback of cached history actions', [
            'total_affected_rows' => $totalAffectedRows,
        ]);
    }
};
