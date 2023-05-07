<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ProcessAskClientForWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, DmccTraderHelperTrait;

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
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        /** @var TraderOrder $traderOrder */
        $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);

        $dict = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version);
        $currentStep = $dict->getStepByHistory(FinancingOrderHistory::WaitingClientWakala);
        $previousStep = $dict->getPreviousStepOf($currentStep->step);
        $history = end($previousStep->histories);

        if (! $traderOrder->doesLastActionMatchWith($history)) {
            return;
        }

        $financingOrder = $traderOrder->order;

        if ($financingOrder->is_verification_required) {
            app()->make(AskClientWakala::class)->handle(
                $financingOrder,
                Str::replace('{order_id}', $financingOrder->id, Config::get('frontend.client_wakala_url'))
            );
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::WaitingClientWakala);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('traderOrder'.$this->traderOrder)];
    }
}
