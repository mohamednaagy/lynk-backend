<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Models\TraderOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\OrderDeliveryConfirmed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Stancl\Tenancy\Database\TenantScope;

class NotifyAdminsAboutOrderDeliveryConfirmed implements ShouldQueue
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

        $admins = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->role(Role::Admin)
            ->get();

        Notification::send($admins, new OrderDeliveryConfirmed($this->traderOrder));
    }
}
