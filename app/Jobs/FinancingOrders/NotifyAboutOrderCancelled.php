<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\OrderCancelled;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAboutOrderCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $canceller)
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
        $lender = $this->financingOrder->lender()->withTrashed()->first();

        $notifiableEmails = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::ORDER_CANCELLED,
                fn ($query) => $query->withLenderAdminForCompany($lender->id)
            )->pluck('email')
            ->all();

        $notification = new OrderCancelled($this->financingOrder, $this->canceller);
        $notification->sendTo($notifiableEmails);
    }
}
