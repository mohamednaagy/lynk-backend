<?php

namespace Jobs\Bursam\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
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

class ProcessBursamStbCertificateAfterCancellationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected static CommittedOrder $financingOrder;

    protected static Model|TraderOrder $traderOrder;

    public function setUp(): void
    {
        parent::setUp();

        self::$financingOrder = OrderScenario::inProgress()
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder(driver: 'bursam', status: TraderOrderStatus::PendingCancellation, data: [
            'version' => 'v2',
        ]);

    }

    public function test_get_owner_to_customer_certificate_after_cancellation_if_commodity_already_sold()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->moveToHistory(FinancingOrderHistory::GetSellingToMarketCertificate);

        (new ProcessBursamStbCertificateAfterCancellation(self::$traderOrder->id, TraderOrderCancelReason::Manual))->handle();

        self::$traderOrder->refresh();

        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::Cancelled));
        $this->assertEquals(TraderOrderCancelReason::Manual, self::$traderOrder->cancel_reason);
    }

    public function test_get_owner_to_customer_certificate_after_cancellation_if_commodity_not_sold()
    {
        Event::fake();
        Http::fake(function () {
            return Http::response([
                'ECERTNO' => 'OLN03SEP23-0000002-000',
                'BUYER' => 'LYNK LLC',
                'OWNER' => 'LYNK LLC',
                'BIDNO' => '12',
                'TOTALVALUE' => '1.00',
                'CURRENCY' => 'SAR',
                'PRICE' => '4,680.51675978',
                'PRICE_MYR_EQUIVALENT' => '5,362.00',
                'PURCHASETIMEDATE' => '10:29:31.703 03 Sep 2023',
                'VALUEDATE' => '03 Sep 2023',
                'PNAME' => 'OLN-MSIA-12',
                'PVOLUME' => '0.00021447',
                'LINE' => [
                    [
                        'SUPPLIER' => 'CSP 10',
                        'VOLUME' => '0.00021447',
                    ],
                ],
            ]);
        });

        (new ProcessBursamStbCertificateAfterCancellation(self::$traderOrder->id, TraderOrderCancelReason::Manual))->handle();

        self::$traderOrder->refresh();

        $this->assertNotNull(self::$traderOrder->getFirstMedia(TraderOrderMediaCollection::BursamTtiHoldingCertificate));
        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetSellingToMarketCertificate));
        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::Cancelled));
    }

    public function test_get_owner_to_customer_certificate_after_cancellation_if_commodity_not_sold_failed()
    {
        Http::fake(function () {
            return Http::response([
                'SUCCESSYN' => 'N',
            ]);
        });

        $this->expectException(TraderException::class);

        (new ProcessBursamStbCertificateAfterCancellation(self::$traderOrder->id, TraderOrderCancelReason::Manual))->handle();
    }
}
