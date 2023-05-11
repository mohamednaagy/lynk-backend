<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs\V1;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccPtpNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

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
     *
     * @return void
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

            if (! $this->traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
                return;
            }

            $trader = Trader::driver($this->traderOrder->provider, $this->traderOrder->version);

            $trader->respondPtpService($this->ttiId);

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::RespondPtp
            );
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
