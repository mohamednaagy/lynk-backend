<?php

namespace App\Console;

use App\Jobs\General\ProcessFinancingOrders;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessDailySellingPendingCommodityToMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessDailySoldCommodityToMarket;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Config;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (is_bursam_service_available()) {
            $schedule->job(new ProcessFinancingOrders())->everyMinute()->withoutOverlapping();
            $schedule->job(new ProcessDmccNotifications())->everyMinute()->withoutOverlapping();
        }

        $timezone = Config::get('services.bursam.timezone');
        $marketOpeningStartTime = Config::get('services.bursam.market_opening_start_time');
        $sellingCommodityStartTime = Config::get('services.bursam.selling_commodity_start_time');
        $sellingCommodityEndTime = Config::get('services.bursam.selling_commodity_end_time');

        $schedule->job(new ProcessDailySellingPendingCommodityToMarket())
            ->timezone($timezone)
            ->daily()
            ->between($sellingCommodityStartTime, $sellingCommodityEndTime);

        $schedule->job(new ProcessDailySoldCommodityToMarket())
            ->timezone($timezone)
            ->dailyAt($sellingCommodityEndTime);

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
