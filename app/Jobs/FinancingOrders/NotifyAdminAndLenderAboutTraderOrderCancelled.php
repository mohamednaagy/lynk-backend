<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
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

class NotifyAdminAndLenderAboutTraderOrderCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder, private User $canceller)
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
        $lender = $this->traderOrder->order->lender()->withTrashed()->first();

        $notifiables = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::TRADE_REQUEST_CANCELLED, function ($query) use ($lender) {
                $query->where(function ($query) use ($lender) {
                    $query->role(Role::Admin)
                        ->orWhere(function ($query) {
                            $query->role(Role::Manager)
                                ->permission(
                                    perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Cancel])
                                );
                        })
                        ->orWhere(function ($query) use ($lender) {
                            $query->role(Role::LenderAdmin)
                                ->whereHas('lender', function ($query) use ($lender) {
                                    $query->where('id', $lender->id);
                                });
                        });
                });
            });

        Notification::send($notifiables, new TraderOrderCancelled($this->traderOrder, $this->canceller));
    }
}
