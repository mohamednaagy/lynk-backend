<?php

namespace App\Jobs\Dmcc;

use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccContractSignedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            $lastTraderOrder = $financingOrder->activeTraderOrder()
                ->whereIn('provider', ['dmcc', 'fake'])->first();

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::CommoditySoldToCustomer)) {
                return;
            }

            $trader = Trader::driver($lastTraderOrder->provider);

            $trader->createSellingCommodityToCustomerDocument($lastTraderOrder);

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::CommoditySoldToCustomer);

            app()->make(GenerateClientWakala::class)->handle($financingOrder);
        });
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('financingOrder'.$this->financingOrder)];
    }
}
