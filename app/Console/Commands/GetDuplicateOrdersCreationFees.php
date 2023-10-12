<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GetDuplicateOrdersCreationFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:duplicate-creation-fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get duplication creation fees';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $traderOrderIds = TraderOrder::pluck('id');
        $financingOrderIds = FinancingOrder::pluck('id');

        $dupTraderOrderIds = Transaction::whereIn(
            'meta->trader_order_id',
            $traderOrderIds
        )
            ->where('reason', TransactionReason::OrderCreationFee)
            ->get()
            ->pluck('meta.trader_order_id')
            ->duplicates()
            ->toArray();

        $dupFinOrderIds = Transaction::whereIn(
            'meta->financing_order_id',
            $financingOrderIds
        )
            ->whereNull('meta->trader_order_id')
            ->where('reason', TransactionReason::OrderCreationFee)
            ->get()
            ->pluck('meta.financing_order_id')
            ->duplicates()
            ->toArray();

        Storage::put(
            $filename = sprintf(
                'duplic-creation-fees-%s-%s.json',
                now()->toDateTimeString(),
                Str::random(5)
            ),
            json_encode([
                'fin_orders_ids' => $dupFinOrderIds,
                'trader_orders_ids' => $dupTraderOrderIds,
            ])
        );

        $this->info(sprintf('File: %s', $filename));

        return Command::SUCCESS;
    }
}
