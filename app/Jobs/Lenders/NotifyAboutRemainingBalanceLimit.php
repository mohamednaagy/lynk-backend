<?php

declare(strict_types=1);

namespace App\Jobs\Lenders;

use App\Enums\SystemNotificationType;
use App\Models\Lender;
use App\Notifications\WalletRemainingBalanceNotification;
use App\Services\Company\CompanyLenderClientService;
use App\Services\NotificationPreferenceService;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAboutRemainingBalanceLimit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Lender $lender)
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

        $currentBalance = CompanyLenderClientService::getLenderCurrentBalance($this->lender);

        if (($this->lender->lenderDetail->min_wallet_limit ?? 0) <= $currentBalance) {
            Log::channel(LOG_CHANNEL_LYNK)
                ->info('Lender has enough balance in his wallet', [
                    'lender_id' => $lenderId,
                    'balance' => $currentBalance,
                    'limit' => $this->lender->lenderDetail->min_wallet_limit,
                ]);

            return;
        }

        $notification = new WalletRemainingBalanceNotification($this->lender, $currentBalance);
        $notification->sendTo($notifiableEmails);
    }
}
