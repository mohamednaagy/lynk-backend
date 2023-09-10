<?php

namespace Jobs\Bursam\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\TraderException;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamBidCertificate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;

class ProcessBursamBidCertificateTest extends TestCase
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
            'original_data' => ['unit' => 'Tonnages'],
        ]);
    }

    public function test_get_bid_certificate()
    {
        Queue::fake();
        TraderOrderScenario::of(self::$traderOrder)
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument);
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

        (new ProcessBursamBidCertificate(self::$traderOrder->id))->handle();

        self::$traderOrder->refresh();

        $this->assertNotNull(self::$traderOrder->products);
        $this->assertNotNull(self::$traderOrder->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate));
        $this->assertNotNull(self::$traderOrder->getFirstMedia(TraderOrderMediaCollection::ClientWakala));
        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachTtiHoldingCertificateDocument));
    }

    public function test_get_bid_certificate_fail()
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->moveToHistory(FinancingOrderHistory::GetTtiHoldingCertificateDocument);
        Http::fake(function () {
            return Http::response([
                'SUCCESSYN' => 'N',
            ]);
        });

        $this->expectException(TraderException::class);

        (new ProcessBursamBidCertificate(self::$traderOrder->id))->handle();
    }

    public function test_get_bid_certificate_when_fetch_ynn_not_completed()
    {
        Queue::fake();

        (new ProcessBursamBidCertificate(self::$traderOrder->id))->handle();

        self::$traderOrder->refresh();

        $this->assertNull(self::$traderOrder->products);
        $this->assertNull(self::$traderOrder->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate));
        $this->assertNull(self::$traderOrder->getFirstMedia(TraderOrderMediaCollection::ClientWakala));
        $this->assertFalse(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachTtiHoldingCertificateDocument));
    }
}
