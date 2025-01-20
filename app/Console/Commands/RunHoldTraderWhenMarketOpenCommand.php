<?php

namespace App\Console\Commands;

use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Console\Command;

class RunHoldTraderWhenMarketOpenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:hold-bursa-traders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run ALl Hold Traders when Market Is Open';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $holdTrader = TraderOrder::getFirstHoldTraderOrder();
        if ($holdTrader) {
            Trader::driver($holdTrader->provider, $holdTrader->version)->moveHoldTraderOrder($holdTrader);
        }

        return Command::SUCCESS;
    }
}
