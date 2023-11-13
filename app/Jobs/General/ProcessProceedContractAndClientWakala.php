<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ProcessProceedContractAndClientWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected int $traderOrderId,
    ) {
    }

    /**
     * Execute the job.
     *
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws Exception
     */
    public function handle(MakeOrderProceed $makeOrderProceed): void
    {
        \DB::transaction(function () use ($makeOrderProceed) {
            /** @var TraderOrder $traderOrder */
            $traderOrder = TraderOrder::query()->withLastHistoryAction()->lockForUpdate()->findOrFail($this->traderOrderId);

            if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                return;
            }

            $traderDictionary = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version);
            $currentStepNode = $traderDictionary->getCompletedStepOrPreviousByHistory($traderOrder->last_history_action);
            $nextStepNode = $traderDictionary->getNextStepOf($currentStepNode->step);

            $proceedAction = match ($nextStepNode->step) {
                MurabhaStep::ClientWakala => FinancingOrderProceedCase::ClientWakalaAccepted,
                MurabhaStep::ContractSigned => FinancingOrderProceedCase::ContractSigned,
                default => null,
            };

            if ($proceedAction) {
                $makeOrderProceed->handle($traderOrder, $proceedAction, false);
            }

            if (
                ! $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned)
                || ! $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala)
            ) {
                self::dispatch($this->traderOrderId)->delay(now()->addSeconds(5));
            }
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }
}
