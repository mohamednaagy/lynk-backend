<?php

namespace App\Console;

use App\Jobs\General\ProcessFinancingOrders;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamCredential;
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
        if ($this->isBursamServiceAvailable()) {
            $schedule->job(new ProcessFinancingOrders())->everyMinute()->withoutOverlapping();
            $schedule->job(new ProcessDmccNotifications())->everyMinute()->withoutOverlapping();
        }

        $schedule->job(new ProcessBursamCredential())->dailyAt('19:20')
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping();
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

    private function isBursamServiceAvailable()
    {
        $timezone = 'Asia/Riyadh';
        $now = now($timezone);
        $marketOpeningStartTime = '19:30:00';
        $marketOpeningEndTime = '18:30:00';
        $fridayBreakStartTime = '08:15:00';
        $fridayBreakEndTime = '08:45:00';

        $marketOpeningStartDateTime = now($timezone)->setTimeFromTimeString($marketOpeningStartTime)->subDay();
        $marketOpeningEndDateTime = now($timezone)->setTimeFromTimeString($marketOpeningEndTime);
        $fridayBreakStartDateTime = now($timezone)->setTimeFromTimeString($fridayBreakStartTime);
        $fridayBreakEndDateTime = now($timezone)->setTimeFromTimeString($fridayBreakEndTime);

        if ($now->isAfter($marketOpeningEndDateTime)) {
            $marketOpeningEndDateTime->addDay();
        } else {
            $marketOpeningStartDateTime->subDay();
        }

        if (
            ! $now->between($marketOpeningStartDateTime, $marketOpeningEndDateTime)
            || ($now->isFriday() && $now->between($fridayBreakStartDateTime, $fridayBreakEndDateTime))
        ) {
            return false;
        }

        return true;
    }
}
