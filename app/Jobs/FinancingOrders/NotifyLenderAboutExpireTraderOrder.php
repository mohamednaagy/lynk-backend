<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Notifications\FinancingOrders\TraderOrderExpired;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyLenderAboutExpireTraderOrder implements ShouldQueue
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
        $lender = $this->traderOrder->order->lender()->withTrashed()->first();

        $notifiables = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::TRADE_REQUEST_CANCELLED, function ($query) use ($lender) {
                $query->where(function ($query) use ($lender) {
                    $query->role(Role::LenderAdmin)
                        ->whereHas('lender', function ($query) use ($lender) {
                            $query->where('id', $lender->id);
                        });
                });
            });

        Notification::send($notifiables, new TraderOrderExpired($this->traderOrder));
    }
}
