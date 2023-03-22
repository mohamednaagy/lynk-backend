<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Models\TraderOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\TraderOrders\TraderOrderProgressStopped;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Stancl\Tenancy\Database\TenantScope;

class NotifyAdminsIfTraderOrderHasStopped implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected TraderOrder $traderOrder, protected int $historyActionBeforeDispatching)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $traderOrder = $this->traderOrder
            ->query()
            ->withoutGlobalScope(TenantScope::class)
            ->withLastHistoryAction()
            ->first();

        if ($traderOrder->last_history_action === $this->historyActionBeforeDispatching) {
            $admins = User::role([Role::Admin])->get();
            Notification::send($admins, new TraderOrderProgressStopped($this->traderOrder));
        }
    }
}
