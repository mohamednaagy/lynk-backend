<?php

namespace Jobs\Bursam\V2;

use App\Enums\FinancingOrderHistory;
use App\Exceptions\TraderException;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultNYY;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;

class ProcessBursamOrderResultNYYTest extends TestCase
{
    use RefreshDatabase;

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
            ->reset()
            ->moveToHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);
    }

    public function test_check_if_order_sold_to_market()
    {
        Event::fake();
        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => 0,
                ],
                'body' => [
                    [
                        'otcErrNo' => '999',
                        'stbErrNo' => '999',
                    ],
                ],
            ], 200);
        });

        (new ProcessBursamOrderResultNYY(self::$traderOrder->id))->handle();

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CommoditySoldToMarket));
    }

    public function test_check_if_order_not_sold_to_market()
    {
        Event::fake();
        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => 0,
                ],
                'body' => [
                    [
                        'otcErrNo' => rand(0, 998),
                        'stbErrNo' => rand(0, 998),
                    ],
                ],
            ], 200);
        });

        $this->expectException(TraderException::class);

        (new ProcessBursamOrderResultNYY(self::$traderOrder->id))->handle();
    }
}
