<?php

namespace App\Jobs;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Drivers\FakeDmccDriver;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccInProgressOrder implements ShouldQueue
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
            if (Trader::driver() instanceof FakeDmccDriver) {
                Trader::driver()->updateOrderStatus($financingOrder, FinancingOrderStatus::MurabahaSaleCompleted);
            }
            // TODO: check url value
//            app()->make(AskClientWakala::class)->handle($financingOrder, 'http://localhost');
            Trader::driver('dmcc')->updateOrderStatus($financingOrder, FinancingOrderStatus::WaitingClientWakala);
        });
    }
}
