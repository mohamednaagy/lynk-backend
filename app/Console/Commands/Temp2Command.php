<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Models\Lender;
use App\Notifications\WalletRemainingBalanceNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Traits\Localizable;

class Temp2Command extends Command
{
    use Localizable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:temp2-command';

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

        $notifiables = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::WALLET_REMAINING_BALANCE,
                fn ($query) => $query->role([Role::Admin, Role::Manager])
                    ->orWhere(fn ($q) => $q->withLenderAdminForCompany($lender->id))
            );
        //        dd($notifiables);
        $this->withLocale('ar', function () use ($lender, $notifiables) {
            Notification::send($notifiables, new WalletRemainingBalanceNotification($lender, 480));
        });

        $this->info('Done!!!');
    }
}
