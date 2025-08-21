<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use App\Support\Traders\Traits\TraderHelperTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessProceedClientWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure, TraderHelperTrait;

    protected string $channel = LOG_CHANNEL_BURSAM;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected int $traderOrderId,
    ) {}

    /**
     * Execute the job.
     *
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws Exception
     */
    public function handle(MakeOrderProceed $makeOrderProceed): void
    {
        $traderOrder = TraderOrder::query()->findOrFail($this->traderOrderId);

        if ($this->isClientWakalaStepCompleted($traderOrder)) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('Skipped ProceedClientWakalaAccepted: already completed' , $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::WaitingClientWakala)) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('ProceedClientWakalaAccepted WaitingClientWakala not complete' , $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
                'last_action' => $traderOrder->trader_order_history->latest()->first()->action,
                'expected_action' => FinancingOrderHistory::WaitingClientWakala,
            ]);

            return;
        }
        $makeOrderProceed->handle($traderOrder, FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ClientWakalaAccepted), false);
        Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('ProceedClientWakalaAccepted completed successfully' , $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrderId,
        ]);
    }

    protected function isClientWakalaStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala);
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}
