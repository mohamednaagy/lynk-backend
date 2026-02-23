<?php

namespace App\Console;

use App\Console\Commands\GenerateSupplierMonthlyUsageReportsCommand;
use App\Console\Commands\RunHoldTraderWhenMarketOpenCommand;
use App\Console\Commands\SendInProgressOrdersNotificationCommand;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessDailySellingPendingCommodityToMarket;
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

        $schedule->command(RunHoldTraderWhenMarketOpenCommand::class)
            ->timezone($timezone)
            ->when(is_bursam_service_available())
            ->at(get_start_time_bursa()->format('H:i'));

        $sellingCommodityStartTime = Config::get('services.bursam.selling_commodity_start_time');
        $sellingCommodityEndTime = Config::get('services.bursam.selling_commodity_end_time');

        $schedule->job(new ProcessDailySellingPendingCommodityToMarket)
            ->timezone($timezone)
            ->everyTwoMinutes()
            ->between($sellingCommodityStartTime, $sellingCommodityEndTime)
            ->onOneServer();

        $schedule->command(GenerateSupplierMonthlyUsageReportsCommand::class)
            ->monthlyOn(1, '02:00')
            ->onOneServer();

        $schedule->command(SendInProgressOrdersNotificationCommand::class)
            ->dailyAt('17:00')
            ->timezone($timezone)
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
