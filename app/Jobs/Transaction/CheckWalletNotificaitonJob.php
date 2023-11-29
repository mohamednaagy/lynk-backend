<?php

namespace App\Jobs\Transaction;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\WalletNotificationType;
use App\Models\Company;
use App\Models\TieredPricing;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletNotification;
use App\Notifications\WalletReachedThreshold;
use Cknow\Money\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Stancl\Tenancy\Database\TenantScope;

class CheckWalletNotificaitonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Wallet $wallet)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $company = $this->wallet->holder;

        if (! $company instanceof Company) {
            return;
        }

        $notifiaction = WalletNotification::where('company_id', $company->id)
            ->where('wallet_id', $this->wallet->id)
            ->first();
        $balance = $this->wallet->balance;

        if (! $notifiaction) {
            return;
        }

        $doesReachedThreshold = match ($notifiaction->type->value) {
            WalletNotificationType::ORDER_COUNT => $this->getOrderCount($company, $balance)->lessThanOrEqual($notifiaction->value),
            WalletNotificationType::WALLET_BALANCE => $balance->lessThanOrEqual($notifiaction->value),
        };

        if (! $doesReachedThreshold) {
            return;
        }

        if ($notifiaction->isNotified()) {
            return;
        }

        $notifiables = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('company_id', $company->id)
            ->where(function ($query) {
                $query->role(Role::LenderAdmin)
                    ->orWhere(function ($query) {
                        $query->permission(
                            perm_arr(Area::Lender, [Subject::WalletNotifications, Action::Index, Action::Manage])
                        );
                    });
            })
            ->get();

        Notification::send($notifiables, new WalletReachedThreshold($notifiaction));

        $notifiaction->markAsNotified();
    }

    private function getOrderCount(Company $company, Money $balance): ?Money
    {
        if (! $company->isStandard()) {
            return new Money(0);
        }

        $orderCostWithVat = TieredPricing::getOrderCostIfStandard($company)['costWithVat'];

        return $balance->divide($orderCostWithVat->getAmount(), \Money\Money::ROUND_DOWN);
    }
}
