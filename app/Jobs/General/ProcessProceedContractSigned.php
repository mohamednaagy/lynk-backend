<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
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

class ProcessProceedContractSigned implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure , TraderHelperTrait;

    protected string $channel = 'bursam';

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
        if (
            $this->isPreviousStepOfContractSignedNotCompleted($traderOrder) || $this->isContractSignedStepCompleted($traderOrder)
        ) {
            return;
        }
        $makeOrderProceed->handle($traderOrder, FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ContractSigned), false);

        Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle(' finish ProcessProceedContractSigned' , $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrderId,
        ]);
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }

    protected function isPreviousStepOfContractSignedNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
                ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
        );
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
