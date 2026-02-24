<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\OrderRequiresApproval;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAboutOrderRequiresApproval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private int $financingOrderId, private User $user)
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
        $financingOrder = FinancingOrder::find($this->financingOrderId);

        if (! $financingOrder) {
            Log::channel(LOG_CHANNEL_LYNK)->error('NotifyAboutOrderRequiresApproval: Financing order not found', ['financing_order_id' => $this->financingOrderId]);

            return;
        }

        $notifiableEmails = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::ORDER_REQUIRES_APPROVAL, function ($query) use ($financingOrder) {
                $query->where(function ($query) use ($financingOrder) {
                    $query->withoutRole(Role::Admin)
                        ->where(function ($query) use ($financingOrder) {
                            $query->role(Role::LenderAdmin)
                                ->whereHas('lender', function ($query) use ($financingOrder) {
                                    $query->where('id', $financingOrder->company_id);
                                });
                        });
                });
            })->pluck('email')
            ->all();

        $notification = new OrderRequiresApproval($financingOrder, $this->user);
        $notification->sendTo($notifiableEmails);
    }
}
