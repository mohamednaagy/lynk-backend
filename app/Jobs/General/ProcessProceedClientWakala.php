<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedClientWakalaAccepted;
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

class ProcessProceedClientWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure, TraderHelperTrait;

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
    public function handle(): void
    {
        $traderOrder = TraderOrder::query()->withLastHistoryAction()->findOrFail($this->traderOrderId);

        if ($this->isClientWakalaStepCompleted($traderOrder)) {
            Log::channel('bursam')->info('Skipped ProceedClientWakalaAccepted: already completed', [
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        if ($this->isPreviousStepOfClientWakalaNotCompleted($traderOrder)) {
            Log::channel('bursam')->info('release 10 sec for ProceedClientWakalaAccepted', [
                'traderOrderId' => $this->traderOrderId,
            ]);
            $this->release(10);

            return;
        }

        app(ProceedClientWakalaAccepted::class)->handle($traderOrder);
        Log::channel('bursam')->info('ProceedClientWakalaAccepted completed successfully', [
            'traderOrderId' => $this->traderOrderId,
        ]);
    }

    protected function isPreviousStepOfClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $previousStep = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
            ->getPreviousStepOf(MurabhaStep::ClientWakala)->step;

        return ! $traderOrder->checkOrderStepComplete($previousStep);
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
