<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GetTransactionsWithoutTraderOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:without-trader-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get transactions that don\'t have trader orders';

    protected $toBeDeletedTransactions = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transactionsWithTraderOrders = Transaction::where('reason', TransactionReason::OrderCreationFee)
            ->whereNotNull('meta->trader_order_id')
            ->select('id', DB::raw('json_extract(meta, "$.trader_order_id") as trader_order_id'))
            ->get();
        $traderOrdersIds = $transactionsWithTraderOrders->pluck('trader_order_id');

        $transactionsWithFinOrders = Transaction::where('reason', TransactionReason::OrderCreationFee)
            ->whereNull('meta->trader_order_id')
            ->select('id', DB::raw('json_extract(meta, "$.financing_order_id") as financing_order_id'))
            ->get();
        $financingOrdersIds = $transactionsWithFinOrders->pluck('financing_order_id');

        $existingTraderOrdersIds = TraderOrder::whereIn('id', $traderOrdersIds)->pluck('id');
        $existingFinOrdersIds = FinancingOrder::whereIn('id', $financingOrdersIds)->pluck('id');

        $deletedFinOrdersIds = array_diff(
            $financingOrdersIds->toArray(),
            $existingFinOrdersIds->toArray()
        );
        $this->toBeDeletedTransactions = array_merge(
            $this->toBeDeletedTransactions,
            $transactionsWithFinOrders->whereIn('financing_order_id', $deletedFinOrdersIds)
                ->pluck('id')
                ->toArray()
        );

        $deletedTraderOrdersIds = array_diff(
            $traderOrdersIds->toArray(),
            $existingTraderOrdersIds->toArray()
        );
        $this->toBeDeletedTransactions = array_merge(
            $this->toBeDeletedTransactions,
            $transactionsWithTraderOrders->whereIn('trader_order_id', $deletedTraderOrdersIds)
                ->pluck('id')
                ->toArray()
        );

        Storage::put(
            $filename = sprintf(
                'transactions-wo-orders-%s-%s.json',
                now()->toDateTimeString(),
                Str::random(5)
            ),
            json_encode([
                'transactions' => $this->toBeDeletedTransactions,
            ])
        );

        $this->info(sprintf('File: %s', $filename));

        return Command::SUCCESS;
    }
}
