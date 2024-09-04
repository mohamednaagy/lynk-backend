<?php

namespace App\Console\Commands;

use App\Models\FinancingOrder;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateChargedTraderOrdersCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:charged-trader-orders-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update charged_trader_orders_count based on transaction amounts for the financing order';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting the update process...');

        DB::transaction(function () {
            FinancingOrder::where('update_charged_count_status', 0)
                ->chunkById(1000, function ($financingOrders) {
                    foreach ($financingOrders as $order) {
                        $this->updateFinancingOrderCount($order);
                    }
                });
        });

        $this->info('Update process completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Update the charged_trader_orders_count for the given financing order.
     *
     * @param FinancingOrder $order
     * @return void
     */
    private function updateFinancingOrderCount(FinancingOrder $order): void
    {
        // Calculate the new charged_trader_orders_count based on related transactions
        $count = Transaction::whereFinancingOrderId($order->id)
            ->sum(DB::raw('IF(amount > 0, -1, 1)'));
            
        // Calculate the old charged_trader_orders_count (old logic)
        $oldCount = $order->loadCount([
            'traderOrders as old_count' => function ($query) {
                $query->whereNull('data->refunded_at');
            }
        ]);

        // Check if the new count is different from the old one or new count is negative
        if ($count != $oldCount->old_count || $count < 0) {
            $order->update([
                'update_charged_count_status' => 1, // ERROR
                'old_charged_trader_orders_count' => $oldCount->old_count,
            ]);

            if ($count >= 0) {
                $order->update([
                    'charged_trader_orders_count' => $count,
                    'update_charged_count_status' => 1, // ERROR
                    'old_charged_trader_orders_count' => $oldCount->old_count,
                ]);
            }
        } else {
            // Update the order's count if no issues were found
            $order->update([
                'charged_trader_orders_count' => $count,
                'old_charged_trader_orders_count' => $oldCount->old_count,
                'update_charged_count_status' => 2, // DONE
            ]);
        }
    }
}
