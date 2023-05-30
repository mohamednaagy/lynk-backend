<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\OrderCanceled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Stancl\Tenancy\Database\TenantScope;

class NotifyAdminAndLenderAboutOrderCanceled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $user)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $company = $this->financingOrder->company;

        $notifiables = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->role(Role::Admin)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->role(Role::Admin);
                });
            })
            ->orWhere(function ($query) {
                $query->where(function ($query) {
                    $query->role(Role::Manager)
                        ->permission(
                            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Cancel])
                        );
                });
            })
            ->orWhere(function ($query) use ($company) {
                $query->role(Role::LenderAdmin)
                    ->whereHas('company', function ($query) use ($company) {
                        $query->where('id', $company->id);
                    });
            })
            ->get();

        Notification::send($notifiables, new OrderCanceled($this->financingOrder, $this->user));
    }
}
