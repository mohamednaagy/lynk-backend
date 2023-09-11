<?php

namespace Jobs\Bursam\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamCancelTimeOutOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarketForCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;

class ProcessBursamCancelTimeOutOrderTest extends TestCase
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
            ->moveToHistory(FinancingOrderHistory::AttachTtiHoldingCertificateDocument);
    }

    public function test_cancel_order_when_timeout()
    {
        Bus::fake();

        (new ProcessBursamCancelTimeOutOrder(self::$financingOrder->model()))->handle();

        self::$traderOrder->refresh();

        $this->assertTrue(self::$traderOrder->status->is(TraderOrderStatus::PendingCancellation));
        Bus::assertChained([
            ProcessBursamSellingCommodityToOpenMarketForCancellation::class,
            ProcessBursamStbCertificateAfterCancellation::class,
            CallQueuedClosure::class,
        ]);
    }
}
