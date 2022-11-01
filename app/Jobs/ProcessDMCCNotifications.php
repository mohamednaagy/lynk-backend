<?php

namespace App\Jobs;

use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDMCCNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $response = Trader::driver()->fetchNotification();

        collect(
            $response->NotificationAllDetailsResponse[0]->notificationAllDetailsResponse->notificationDetails
        )->each(function ($notification) {
            if (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Action Required for Promise to Purchase'
            ) {
                ProcessDMCCPTPNotification::dispatch($notification);
            } elseif (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Action Required for Issue Murabaha Purchase Offer'
            ) {
                ProcessDMCCMPONotification::dispatch($notification);
            }
        });
    }
}
