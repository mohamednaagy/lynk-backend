<?php

namespace App\Console;

use App\Jobs\General\ProcessFinancingOrders;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamCredential;
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
        $now = now();
        $startTime = '19:30:00';
        $endTime = '18:30:00';
        $fridayRestStartTime = '08:15:00';
        $fridayRestEndTime = '08:45:00';
        $timezone = 'Asia/Riyadh';

        // Convert the start and end times to the specified timezone
        $startTime = $now->setTimeFromTimeString($startTime)->setTimezone($timezone);
        $endTime = $now->setTimeFromTimeString($endTime)->setTimezone($timezone);
        $fridayRestStartTime = $now->setTimeFromTimeString($fridayRestStartTime)->setTimezone($timezone);
        $fridayRestEndTime = $now->setTimeFromTimeString($fridayRestEndTime)->setTimezone($timezone);

        // Check if today is Friday and the current time is within the Friday rest time window
        if ($now->isFriday() && $now->between($fridayRestStartTime, $fridayRestEndTime)) {
            return false;
        }

        // Check if the current time is within the available times
        if ($now->between($startTime, $endTime)) {
            return true;
        } else {
            return false;
        }
    }
}
