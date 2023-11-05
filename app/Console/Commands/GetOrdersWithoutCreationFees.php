<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GetOrdersWithoutCreationFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:orders-wo-fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get orders without creation fees';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $financingOrderIds = FinancingOrder::pluck('id');

        $traderOrderIdsWithoutCreationFees = [];

        foreach ($financingOrderIds as $finOrderId) {
            $transactionOnFinOrder = Transaction::where('meta->financing_order_id', $finOrderId)
                ->whereNull('meta->trader_order_id')
                ->where('reason', TransactionReason::OrderCreationFee)
                ->first();

            if ($transactionOnFinOrder) {
                continue;
            }

            $traderOrders = TraderOrder::where('financing_order_id', $finOrderId)
                ->get();

            foreach ($traderOrders as $traderOrder) {
                $transaction = Transaction::where('meta->trader_order_id', $traderOrder->id)
                    ->where('reason', TransactionReason::OrderCreationFee)
                    ->first();

                if ($transaction) {
                    continue;
                }

                $traderOrderIdsWithoutCreationFees[] = $traderOrder->id;
            }
        }

        Storage::put(
            $filename = sprintf(
                'trader-orders-wo-fees-%s-%s.json',
                now()->toDateTimeString(),
                Str::random(5)
            ),
            json_encode([
                'trader_orders_ids' => $traderOrderIdsWithoutCreationFees,
            ])
        );

        $this->info(sprintf('File: %s', $filename));

        return Command::SUCCESS;
    }
}
