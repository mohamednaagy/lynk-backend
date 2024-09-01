<?php

namespace App\Console\Commands;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForNewOrder;
use App\Models\TraderOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateOrderFeeTransactionsForTraderOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:create-order-fee-transactions {--filename=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create order transaction fees';

    protected $traderOrdersIds;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->loadTraderOrderIdsFromStorage();

        /*
         *We paused the deduction to determine if it serves another purpose 
         *and will reassess its impact after one month to decide whether to keep or remove it.
        */
        Log::warning("[CreateOrderFeeTransactionsForTraderOrders command called from someone and shouldn't ");

        // DB::multipleTransaction(function () {
        //     foreach ($this->traderOrdersIds as $traderOrderId) {
        //         app(DeductBalanceForNewOrder::class)->handle(
        //             TraderOrder::find($traderOrderId)
        //         );
        //     }
        // });

        return Command::SUCCESS;
    }

    protected function loadTraderOrderIdsFromStorage()
    {
        $rawContent = Storage::get($this->option('filename'));

        if ($rawContent === null) {
            $this->error('File doesn\'t exist');

            return Command::FAILURE;
        }

        $this->traderOrdersIds = json_decode($rawContent, true)['trader_orders_ids'];
    }
}
