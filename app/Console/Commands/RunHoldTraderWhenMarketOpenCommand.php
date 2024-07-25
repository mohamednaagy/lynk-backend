<?php

namespace App\Console\Commands;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
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

        $trader = TraderOrder::where('status', TraderOrderStatus::Hold)->where('mode', TraderOrderMode::Automatic)->latest()->first();
        //        foreach ($traders as $trader){
        $checkCanChangeStatusOfTrader = Trader::driver($trader->provider, $trader->version)->checkCanCreateTraderOrder();
        if ($checkCanChangeStatusOfTrader) {
            $trader->update(['status' => TraderOrderStatus::Initiated]);
            $trader->traderHistories()->create(['action' => FinancingOrderHistory::GetTtiId]);
            Trader::driver($trader->provider, $trader->version)->processInitiatedTraderOrder($trader);
        }

        //        }
        return Command::SUCCESS;
    }
}
