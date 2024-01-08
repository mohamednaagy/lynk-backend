<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBursamInitiateTraderOrder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected FinancingOrder $financingOrder)
    {
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(InitiateTraderOrder $initiateTraderOrder)
    {
        return DB::multipleTransaction(function () use ($initiateTraderOrder) {
            try {
                $initiateTraderOrder->handle($this->financingOrder->creator, $this->financingOrder->id);
            } catch (OrderAlreadyHasActiveTraderOrderException $exception) {
                //
            }

            return Command::SUCCESS;
        });
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->financingOrder->id;
    }
}
