<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
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
use Illuminate\Support\Facades\Notification;

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
        $notifiables = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::TRADE_REQUEST_CANCELLED, function ($query) {
                $query->where(function ($query) {
                    $query->role(Role::Admin)
                        ->orWhere(function ($query) {
                            $query->role(Role::LenderAdmin)
                                ->whereHas('lender', function ($query) {
                                    $query->where('id', $this->traderOrder->order->company_id);
                                });
                        });
                });
            });

        Notification::send($notifiables, new TraderOrderCancelled($this->traderOrder, $this->canceller));
    }
}
