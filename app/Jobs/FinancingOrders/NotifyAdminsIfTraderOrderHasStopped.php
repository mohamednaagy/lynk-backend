<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Models\TraderOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\TraderOrders\TraderOrderProgressStopped;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsIfTraderOrderHasStopped implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected TraderOrder $traderOrder)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $financingOrderStatus = $this->traderOrder->order->status->value;

        $nextStepDictNode = app(StepHistoriesDictionary::class)->getNextStepOf($financingOrderStatus);

        if (! $this->traderOrder->checkOrderStepComplete($nextStepDictNode->status)) {
            $admins = User::role([Role::Admin])->get();

            Notification::send($admins, new TraderOrderProgressStopped($this->traderOrder));
        }
    }
}
