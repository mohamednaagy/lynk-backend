<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\TraderOrderCancelled;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAboutTraderOrderCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly TraderOrder $traderOrder, private readonly User $canceller)
    {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $companyId = $this->traderOrder->order->company_id;

        $notifiableEmails = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::TRADE_REQUEST_CANCELLED,
                fn ($query) => $query->withLenderAdminForCompany($companyId)
            )->pluck('email')
            ->all();

        $notification = new TraderOrderCancelled($this->traderOrder, $this->canceller);
        $notification->sendTo($notifiableEmails);
    }
}
