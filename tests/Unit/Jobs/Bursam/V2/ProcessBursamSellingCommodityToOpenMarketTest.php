<?php

namespace Jobs\Bursam\V2;

use App\Enums\FinancingOrderHistory;
use App\Exceptions\TraderException;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;

class ProcessBursamSellingCommodityToOpenMarketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected static CommittedOrder $financingOrder;

    protected static Model|TraderOrder $traderOrder;

    public function setUp(): void
    {
        parent::setUp();

        self::$financingOrder = OrderScenario::inProgress()
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder(driver: 'bursam', data: [
            'version' => 'v2',
        ]);

        TraderOrderScenario::of(self::$traderOrder)
            ->moveToHistory(FinancingOrderHistory::ClientWakalaAccepted);
    }

    public function test_selling_commodity_to_open_market_success()
    {
        Event::fake();
        Http::fake(function () {
            return Http::response([
                'body' => [
                    ['statusCode' => 0],
                ],
            ], 200);
        });

        (new ProcessBursamSellingCommodityToOpenMarket(self::$traderOrder->id))->handle();

        self::$traderOrder->refresh();

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument));
        $this->assertNotNull(self::$traderOrder->uuid_two);
    }

    public function test_selling_commodity_to_open_market_failed()
    {
        Http::fake(function () {
            return Http::response([
                'header' => [
                    'errorCode' => 'unable to sell the commodity',
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        (new ProcessBursamSellingCommodityToOpenMarket(self::$traderOrder->id))->handle();
    }
}
