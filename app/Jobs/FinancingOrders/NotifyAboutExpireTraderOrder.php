<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Notifications\FinancingOrders\TraderRequestExpired;
use App\Services\NotificationPreferenceService;
use App\Support\QueryScoper\Scopes\Notifications\TradeRequestExpiredNotificationScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAboutExpireTraderOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly TraderOrder $traderOrder)
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
            ->getEnabledUsersFor(SystemNotificationType::TRADE_REQUEST_EXPIRED, function ($query) use ($companyId) {
                $scope = new TradeRequestExpiredNotificationScope($companyId);
                $scope->apply($query);
            })->pluck('email')
            ->all();

        $notification = new TraderRequestExpired($this->traderOrder);
        $notification->sendTo($notifiableEmails);
    }
}
