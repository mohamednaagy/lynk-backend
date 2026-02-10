<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Notifications\FinancingOrders\InProgressOrdersNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyAboutInProgressOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private int $lenderId)
    {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $lenderId = $this->lenderId;
        $notifiableEmails = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(
                SystemNotificationType::IN_PROGRESS_ORDERS,
                fn ($query) => $query
                    ->withoutRole(Role::Admin)
                    ->role(Role::LenderAdmin)
                    ->whereHas('lender', fn ($q) => $q->whereKey($lenderId))
            )
            ->pluck('email')
            ->all();

        Notification::route('mail', $notifiableEmails)
            ->notify(new InProgressOrdersNotification($lenderId));
    }
}
