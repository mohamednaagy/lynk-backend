<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Console\Command;

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

        Transaction::whereIn('meta->trader_order_id', $traderOrderIds)
            ->whereIn('meta->financing_order_id', $financingOrderIds)
            ->where('reason', TransactionReason::OrderCreationFee)
            ->get()
            ->pluck('meta.trader_order_id')
            ->duplicates()
            ->toArray();

        Transaction::whereIn('meta->financing_order_id', $financingOrderIds)
            ->whereIn('meta->financing_order_id', $financingOrderIds)
            ->where('reason', TransactionReason::OrderCreationFee)
            ->get()
            ->pluck('meta.trader_order_id')
            ->duplicates()
            ->toArray();

        return Command::SUCCESS;
    }
}
