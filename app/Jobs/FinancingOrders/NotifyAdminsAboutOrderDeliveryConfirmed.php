<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Notifications\FinancingOrders\OrderDeliveryConfirmed;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsAboutOrderDeliveryConfirmed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder)
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
        $admins = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED, function ($query) {
                $query->role(Role::Admin);
            });

        Notification::send($admins, new OrderDeliveryConfirmed($this->traderOrder));
    }
}
