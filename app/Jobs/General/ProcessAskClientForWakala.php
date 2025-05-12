<?php

namespace App\Jobs\General;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAskClientForWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    protected mixed $traderOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($traderOrder)
    {
        $this->traderOrder = $traderOrder;
    }

    /**
     * Execute the job.
     */
    public function handle(TraderOrderProceedCaseService $proceedCaseService): void
    {
        $traderOrder = TraderOrder::query()->findOrFail($this->traderOrder);

        $dict = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type);
        $currentStep = $dict->getStepByHistory(FinancingOrderHistory::WaitingClientWakala);
        $previousStep = $dict->getPreviousStepOf($currentStep->step);
        $lastHistoryOfPreviousStep = end($previousStep->histories);

        if (! $traderOrder->doesLastActionMatchWith($lastHistoryOfPreviousStep)) {
            return;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::WaitingClientWakala);

        if ($proceedCaseService->checkIfTraderHasCase($traderOrder->id, FinancingOrderProceedCase::ContractAndClientWakalaCompleted)) {
            Log::channel('bursam')->info('ProcessAskClientForWakala: traderOrderId: '.$traderOrder->id.' - The Trader has ContractAndClientWakalaCompleted Case');
            Trader::driver($traderOrder->provider, $traderOrder->version)->processProceedContractAndClientWakala($traderOrder);
        }
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('traderOrder'.$this->traderOrder)];
    }
}
