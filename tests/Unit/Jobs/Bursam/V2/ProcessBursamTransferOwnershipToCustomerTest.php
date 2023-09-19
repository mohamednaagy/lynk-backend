<?php

namespace Jobs\Bursam\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;

class ProcessBursamTransferOwnershipToCustomerTest extends TestCase
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
            ->moveToHistory(FinancingOrderHistory::ContractSigned);
    }

    public function test_transfer_ownership_to_customer()
    {
        Event::fake();

        (new ProcessBursamTransferOwnershipToCustomer(self::$traderOrder->id))->handle();

        $this->assertNotNull(self::$traderOrder->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer));
        $this->assertTrue(self::$traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument));
    }
}
