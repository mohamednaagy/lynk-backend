<?php

declare(strict_types=1);

namespace App\Jobs\Lenders;

use App\Enums\SystemNotificationType;
use App\Models\Lender;
use App\Notifications\WalletRemainingBalanceNotification;
use App\Services\NotificationPreferenceService;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAboutRemainingBalanceLimit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Lender $lender, private readonly float $currentBalance)
    {
        $this->onQueue('notifications');
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $lenderId = $this->lender->id;

        $notifiableEmails = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::WALLET_REMAINING_BALANCE,
                fn ($query) => $query->withLenderAdminForCompany($lenderId)
            )->pluck('email')
            ->all();

        $notification = new WalletRemainingBalanceNotification($this->lender, $this->currentBalance);
        $notification->sendTo($notifiableEmails);
    }
}
