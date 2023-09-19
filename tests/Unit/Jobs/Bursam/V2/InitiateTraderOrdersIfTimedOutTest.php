<?php

namespace Tests\Unit\Jobs\Bursam\V2;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\InitiateTraderOrdersIfTimedOut;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamInitiateTraderOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\TestCase;

class InitiateTraderOrdersIfTimedOutTest extends TestCase
{
    use RefreshDatabase;

    protected static CommittedOrder $financingOrder;

    protected static Model|TraderOrder $traderOrder;

    protected static CommittedOrder $otherFinancingOrder;

    protected static Model|TraderOrder $otherTraderOrder;

    public function setUp(): void
    {
        parent::setUp();

        $timezone = Config::get('services.bursam.timezone');
        $marketOpeningStartTimeString = Config::get('services.bursam.market_opening_start_time');
        $marketOpeningEndTimeString = Config::get('services.bursam.market_opening_end_time');

        $marketOpeningStartTime = Carbon::parse($marketOpeningStartTimeString, $timezone)->subDay()->utc();

        $marketOpeningEndTime = Carbon::parse($marketOpeningEndTimeString, $timezone)->utc();

        self::$financingOrder = OrderScenario::inProgress()
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder(driver: 'bursam', status: TraderOrderStatus::Cancelled, data: [
            'version' => 'v2',
            'cancel_reason' => TraderOrderCancelReason::MurabhaTimeout,
            'mode' => TraderOrderMode::Automatic,
            'created_at' => $marketOpeningStartTime->average($marketOpeningEndTime),
        ]);

        self::$otherFinancingOrder = OrderScenario::inProgress()
            ->commit();

        self::$otherTraderOrder = InProgressOrder::of(self::$otherFinancingOrder)->createTraderOrder(driver: 'bursam', status: TraderOrderStatus::Cancelled, data: [
            'version' => 'v2',
            'cancel_reason' => TraderOrderCancelReason::MurabhaTimeout,
            'mode' => TraderOrderMode::Automatic,
            'created_at' => $marketOpeningStartTime->average($marketOpeningEndTime),
        ]);
    }

    public function test_initiate_trader_orders_if_timed_out()
    {
        Queue::fake();

        (new InitiateTraderOrdersIfTimedOut())->handle();

        Queue::assertPushed(ProcessBursamInitiateTraderOrder::class, 2);
    }
}
