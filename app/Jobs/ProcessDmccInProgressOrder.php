<?php

namespace App\Jobs;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $driver = config('trader.default');
        DB::transaction(function () use ($driver) {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            app()->make(AskClientWakala::class)->handle($financingOrder, Str::replace('{order_id}', $financingOrder->id, Config::get('frontent.client_wakala_url')));
            Trader::driver($driver)->updateOrderStatus($financingOrder, FinancingOrderStatus::WaitingClientWakala);
        });
    }
}
