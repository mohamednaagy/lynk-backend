<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SystemNotificationType;
use App\Models\Lender;
use App\Notifications\WalletRemainingBalanceNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Mailable;

class TempCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:temp-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lender = Lender::with('lenderDetail')->findOrFail(598);

        $notifiableEmails = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::ORDER_REQUIRES_APPROVAL,
                fn ($query) => $query->withLenderAdminForCompany($lender->id)
            )->pluck('email')
            ->all();

        $notification = new WalletRemainingBalanceNotification($lender, 580);
        dd($notification instanceof Mailable);
        $notification->sendTo($notifiableEmails);
    }
}
