<?php

namespace App\Jobs;

use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDmccNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::debug('tes', ['h']);
        collect(
            Trader::driver('dmcc')->fetchNotification('ACTIONABLE')
        )->each(function ($notification) {
            Log::debug('tes', [$notification]);
            if (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Action Required for Promise to Purchase'
            ) {
                ProcessDmccPtpNotification::dispatch($notification);
            } elseif (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Action Required for Issue Murabaha Purchase Offer'
            ) {
                ProcessDmccMpoNotification::dispatch($notification);
            }
        });

        collect(
            Trader::driver('dmcc')->fetchNotification('FYI')
        )->each(function ($notification) {
            if (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Murabaha Sale Completed'
            ) {
                ProcessDmccMpoSaleCompleteNotification::dispatch($notification)->chain([
                    new ProcessUnprocessedDmccNotification($notification),
                ]);
            }
        });
    }
}
