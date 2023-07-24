<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderProceedCase;
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

class ProcessProceedOrderAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected int $traderOrder,
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
        /** @var TraderOrder $traderOrder */
        $traderOrder = TraderOrder::query()->withLastHistoryAction()->lockForUpdate()->findOrFail($this->traderOrder);

        if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
            return;
        }

        $traderDictionary = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version);
        $currentStepNode = $traderDictionary->getCompletedStepByHistory($traderOrder->last_history_action);
        $nextStepNode = $traderDictionary->getNextStepOf($currentStepNode->step);

        $clientWakala = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ClientWakala,
            'bursam' => BursamMurabhaStep::ClientWakala,
        };

        $contractSigned = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ContractSigned,
            'bursam' => BursamMurabhaStep::ContractSigned,
        };

        match ($nextStepNode->step) {
            $clientWakala => $makeOrderProceed->handle($traderOrder, FinancingOrderProceedCase::ClientWakalaAccepted, false),
            $contractSigned => $makeOrderProceed->handle($traderOrder, FinancingOrderProceedCase::ContractSigned, false),
        };

        if (! $traderOrder->checkOrderStepComplete($contractSigned) || ! $traderOrder->checkOrderStepComplete($clientWakala)) {
            throw new Exception('Order not completed');
        }
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrder;
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }
}
