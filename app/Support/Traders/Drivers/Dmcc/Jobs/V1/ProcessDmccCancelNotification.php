<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs\V1;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccCancelNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $traderOrder;

    protected string $ttiId;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
        $this->ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $this->traderOrder = TraderOrder::query()
                ->where('reference', $this->ttiId)
                ->where('status', TraderOrderStatus::InProgress)
                ->whereIn('provider', ['dmcc', 'fake'])
                ->lockForUpdate()
                ->first();

            if (! $this->traderOrder) {
                return;
            }

            $trader = Trader::driver($this->traderOrder->provider);

            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->traderOrder->financing_order_id);

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::Cancelled)) {
                return;
            }

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::Cancelled);

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::OrderCancelled
            );

            app(UpdateTraderOrderStatusToCancel::class)->handle($this->traderOrder);
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->ttiId;
    }

    public function getTraderOrder()
    {
        return TraderOrder::query()
            ->where('reference', $this->ttiId)
            ->where('status', TraderOrderStatus::InProgress)
            ->whereIn('provider', ['dmcc', 'fake'])
            ->first();
    }
}
