<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderMode;
use App\Models\FinancingOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class ProcessDailySellingPendingCommodityToMarket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timezone = Config::get('services.bursam.timezone');
        $marketOpeningStartTime = Carbon::parse(Config::get('services.bursam.market_opening_start_time'), $timezone);
        $marketOpeningEndTime = Carbon::parse(Config::get('services.bursam.market_opening_end_time'), $timezone);

        if ($marketOpeningStartTime->greaterThan($marketOpeningEndTime)) {
            $marketOpeningStartTime->subDay();
        }

        FinancingOrder::query()
            ->whereHas('activeTraderOrder', function (Builder $query) use ($marketOpeningEndTime, $marketOpeningStartTime) {
                return $query->where('provider', 'bursam')
                    ->where('version', 'v2')
                    ->where('mode', TraderOrderMode::Automatic)
                    ->whereBetween('created_at', [$marketOpeningStartTime->utc(), $marketOpeningEndTime->utc()]);
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) {
                ProcessBursamCancelTimeOutOrder::dispatch($financingOrder);
            });
    }
}
