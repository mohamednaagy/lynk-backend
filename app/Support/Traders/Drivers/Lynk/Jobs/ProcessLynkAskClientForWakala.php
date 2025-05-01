<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ProcessLynkAskClientForWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('local_market');

    }

    /**
     * Execute the job.
     *
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        /** @var TraderOrder $traderOrder */
        $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrderId);

        $dict = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type);
        $currentStep = $dict->getStepByHistory(FinancingOrderHistory::WaitingClientWakala);
        $previousStep = $dict->getPreviousStepOf($currentStep->step);
        $lastHistoryOfPreviousStep = end($previousStep->histories);

        if (! $traderOrder->doesLastActionMatchWith($lastHistoryOfPreviousStep)) {
            return;
        }


        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::WaitingClientWakala);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('traderOrder'.$this->traderOrderId)];
    }
}
