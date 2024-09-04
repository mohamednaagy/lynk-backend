<?php

namespace App\Console\Commands;

use App\Models\FinancingOrder;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateChargedTraderOrdersCount extends Command
{
    private const STATUS_PENDING = 0;

    private const STATUS_ERROR = 1;

    private const STATUS_DONE = 2;

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

        FinancingOrder::where('update_charged_count_status', self::STATUS_PENDING)
            ->chunkById(1000, function ($financingOrders) {
                foreach ($financingOrders as $financingOrder) {
                    $this->updateFinancingOrderCount($financingOrder);
                }
            });

        $this->info('Update process completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Update the charged_trader_orders_count for the given financing order.
     *
     * @param  FinancingOrder  $order
     */
    private function updateFinancingOrderCount(FinancingOrder $financingOrder): void
    {
        // Calculate the new charged_trader_orders_count based on related transactions
        $chargedTransactionsCount = Transaction::whereFinancingOrderId($financingOrder->id)
            ->sum(DB::raw('IF(amount > 0, -1, 1)'));

        if ($chargedTransactionsCount < 0) {
            Log::error('unexpected negative value for transactions count ', [
                'order_id' => $financingOrder->id,
                'new_count' => $chargedTransactionsCount,
            ]);
            $financingOrder->update([
                'update_charged_count_status' => self::STATUS_ERROR,
            ]);

            return;
        }

        // Calculate the old charged_trader_orders_count (old logic)
        $chargedTransactionsCountOldWay = $financingOrder->loadCount([
            'traderOrders as old_count' => function ($query) {
                $query->whereNull('data->refunded_at');
            },
        ])->old_count;

        $financingOrder->update([
            'charged_trader_orders_count' => $chargedTransactionsCount,
            'old_charged_trader_orders_count' => $chargedTransactionsCountOldWay,
            'update_charged_count_status' => ($chargedTransactionsCount == $chargedTransactionsCountOldWay) ? self::STATUS_DONE : self::STATUS_ERROR,
        ]);
    }
}
