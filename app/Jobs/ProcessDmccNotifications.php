<?php

namespace App\Jobs;

use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        collect(
            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->fetchNotification('ACTIONABLE')
        )->each(function ($notification) {
            try {
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
            } catch (\Throwable $th) {
                //throw $th;
            }
        });

        collect(
            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->fetchNotification('FYI')
        )->each(function ($notification) {
            dd($notification);
            if (
                in_array($notification->notificationHeaderAndEntity->notification, [
                    'Murabaha Sale Completed',
                    'Tradeflow Transaction (Islamic) - Payment Settlement Required',
                ])
            ) {
                ProcessDmccMpoSaleCompleteNotification::dispatch($notification)->chain([
                    new ProcessUnprocessedDmccNotification($notification),
                ]);
            } elseif (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Tradeflow Transaction (Islamic) Cancelled'
            ) {
                ProcessDmccCancelNotification::dispatch($notification);
            }
        });
    }
}
