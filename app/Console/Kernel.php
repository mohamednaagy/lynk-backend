<?php

namespace App\Console;

use App\Jobs\General\ProcessFinancingOrders;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessDailySellingPendingCommodityToMarket;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        if (is_bursam_service_available()) {
            $schedule->job(new ProcessFinancingOrders())->everyMinute()->withoutOverlapping();
            $schedule->job(new ProcessDmccNotifications())->everyMinute()->withoutOverlapping();

            $schedule->job(new ProcessDailySellingPendingCommodityToMarket())
                ->between('18:00', '18:30')
                ->timezone('Asia/Riyadh');
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
