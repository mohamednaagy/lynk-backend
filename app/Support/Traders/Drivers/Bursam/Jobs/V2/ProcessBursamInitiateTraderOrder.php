<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBursamInitiateTraderOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected FinancingOrder $financingOrder)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(InitiateTraderOrder $initiateTraderOrder)
    {
        DB::multipleTransaction(function () use ($initiateTraderOrder) {
            $initiateTraderOrder->handle($this->financingOrder->id);
        });
    }
}
