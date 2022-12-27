<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\OrderCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsAboutOrderCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $user)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $admins = User::role([Role::LenderAdmin, Role::LenderSupervisor])->get();

        Notification::send($admins, new OrderCreated($this->financingOrder, $this->user));
    }
}
