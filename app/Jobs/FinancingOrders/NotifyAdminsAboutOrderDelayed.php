<?php

namespace App\Jobs\FinancingOrders;

use App\Enums\Role;
use App\Models\TraderOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\TraderOrders\StepOrderDelayed;
use App\Support\FinancingOrder\FinancingOrderDictionary;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsAboutOrderDelayed implements ShouldQueue
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
        $status = $this->traderOrder->order->status->value;

        if ($this->traderOrder->checkOrderStepComplete($status)) {
            $stepDictNode = app(FinancingOrderDictionary::class)->getPreviousStepOf($status);

            $lastActionOfPrevStep = $this->traderOrder->traderHistories->where('action', end($stepDictNode->histories))
                ->first();

            if (Carbon::parse($lastActionOfPrevStep->created_at)->addMinutes(5) > now()) {
                $admins = User::role([Role::Admin])->get();

                Notification::send($admins, new StepOrderDelayed($this->traderOrder));
            }
        }
    }
}
