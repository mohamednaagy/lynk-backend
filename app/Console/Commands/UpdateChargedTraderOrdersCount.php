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
            // Chunk through all FinancingOrders
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

    private function updateFinancingOrderCount(FinancingOrder $order): void
    {
        // Calculate the new charged_trader_orders_count based on related transactions
        $count = Transaction::whereFinancingOrderId($order->id)
            ->sum(DB::raw('IF(amount > 0, -1, 1)'));

        // Check if there's a problem (negative count)
        if ($count < 0) {
            $order->update([
                'update_charged_count_status' => 1 // ERROR
            ]);
        } else {
            // Update the order's count
            $order->update([
                'charged_trader_orders_count' => $count,
                'update_charged_count_status' => 2 // DONE
            ]);
        }
    }
}
