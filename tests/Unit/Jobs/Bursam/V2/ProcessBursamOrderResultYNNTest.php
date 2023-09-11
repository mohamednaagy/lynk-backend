<?php

namespace Jobs\Bursam\V2;

use App\Enums\BursamErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultYNN;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\TestCase;

class ProcessBursamOrderResultYNNTest extends TestCase
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
    }

    public function test_create_bursam_trader_request()
    {
        Event::fake();
        Http::fake(function () {
            return Http::response([
                'status' => [
                    'processingCount' => 0,
                ],
                'body' => [
                    [
                        'bidErrNo' => '999',
                        'ecertNo' => Str::uuid(),
                    ],
                ],
            ], 200);
        });

        (new ProcessBursamOrderResultYNN(self::$traderOrder->id))->handle();

        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiHoldingCertificateDocument));
    }

    public function test_create_bursam_trader_request_when_product_is_not_available()
    {
        $failureCode = $this->faker->randomElement(BursamErrorCode::UNAVAILABLE_PRODUCT_ERROR_CODES);
        Http::fake(function () use ($failureCode) {
            return Http::response([
                'body' => [
                    [
                        'bidMsg' => $failureCode,
                        'bidErrNo' => $failureCode,
                        'productCode' => self::$traderOrder->product_code,
                    ],
                ],
            ], 200);
        });

        (new ProcessBursamOrderResultYNN(self::$traderOrder->id))->handle();

        self::$financingOrder->model()->refresh();
        self::$traderOrder->refresh();

        $this->assertTrue(self::$financingOrder->model()->status->is(FinancingOrderStatus::TradingFailure));
        $this->assertEquals($failureCode, self::$traderOrder->failure_reason);
        $this->assertEquals(TraderOrderCancelReason::FailureToPurchase, self::$traderOrder->cancel_reason);
    }
}
