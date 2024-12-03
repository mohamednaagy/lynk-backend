<?php

namespace App\Console;

use App\Console\Commands\RunHoldTraderWhenMarketOpenCommand;
use App\Jobs\General\ProcessFinancingOrders;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessDailySellingPendingCommodityToMarket;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccNotifications;
use Carbon\Carbon;
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

        $timezone = Config::get('services.bursam.timezone');

        $schedule->job(new ProcessFinancingOrders())
            ->when(is_bursam_service_available())
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->job(new ProcessDmccNotifications())
            ->when(is_bursam_service_available())
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $marketOpeningStartTime = Config::get('services.bursam.market_opening_start_time');
        $sellingCommodityStartTime = Config::get('services.bursam.selling_commodity_start_time');
        $sellingCommodityEndTime = Config::get('services.bursam.selling_commodity_end_time');

        $schedule->job(new ProcessDailySellingPendingCommodityToMarket())
            ->timezone($timezone)
            ->everyTwoMinutes()
            ->between($sellingCommodityStartTime, $sellingCommodityEndTime)
            ->onOneServer();

            $schedule->command(RunHoldTraderWhenMarketOpenCommand::class)
            ->timezone($timezone)
            ->when(is_bursam_service_available())
            ->when(function () use ($timezone) {
                $now = now($timezone);
                $startTime = get_start_time_bursa();
                $endTime = get_end_time_bursa();
                
                return !$now->between($endTime, $startTime);
            })
            ->everyTwoMinutes()
            ->onOneServer();

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
