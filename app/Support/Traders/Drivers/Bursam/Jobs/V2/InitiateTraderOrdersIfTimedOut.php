<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class InitiateTraderOrdersIfTimedOut implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $timezone = Config::get('services.bursam.timezone');
        $marketOpeningStartTimeString = Config::get('services.bursam.market_opening_start_time');
        $marketOpeningEndTimeString = Config::get('services.bursam.market_opening_end_time');

        $marketOpeningStartTime = Carbon::parse($marketOpeningStartTimeString, $timezone)->subDay()->utc();

        $marketOpeningEndTime = Carbon::parse($marketOpeningEndTimeString, $timezone)->utc();

        FinancingOrder::query()
            ->whereHas('latestTraderOrder', function ($query) use ($marketOpeningEndTime, $marketOpeningStartTime) {
                return $query->where('status', TraderOrderStatus::Cancelled)
                    ->where('data->cancel_reason', TraderOrderCancelReason::MurabhaTimeout)
                    ->where('provider', 'bursam')
                    ->where('version', 'v2')
                    ->where('mode', TraderOrderMode::Automatic)
                    ->whereBetween('created_at', [$marketOpeningStartTime, $marketOpeningEndTime]);
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) {
                ProcessBursamInitiateTraderOrder::dispatch($financingOrder);
            });
    }
}
