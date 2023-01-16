<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccContractSignedOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccContractSignedOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::Approved,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function test_jobe_process_if_the_active_trader_order_has_dmcc_as_provider()
    {
        self::$order->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);
        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();
        $this->assertNotNull(self::$order->traderOrders()->first());
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::Approved));
    }

    /**
     * @throws \Throwable
     */
    public function test_jobe_process_if_the_active_trader_order_has_fake_as_provider()
    {
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);
        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();
        $this->assertNotNull(self::$order->traderOrders()->first());
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::Approved));
    }

    /**
     * @throws \Throwable
     */
    public function test_jobe_process_if_the_current_order_status_is_contracrSigned()
    {
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);
        self::$order->update(['status' => FinancingOrderStatus::ContractSigned]);
        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();
        $this->assertNotNull(self::$order->traderOrders()->first());
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_that_a_selling_commodity_to_customer_document_is_created()
    {
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);
        self::$order->update(['status' => FinancingOrderStatus::ContractSigned]);
        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();
        $this->assertFileExists(self::$order->media->first()->getPath());
        $this->assertNotNull(self::$order->traderOrders()->first());
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }

    /**
     * @throws \Throwable
     */
    public function test_that_the_order_status_is_update_to_commoditySoldToCustomer()
    {
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);
        self::$order->update(['status' => FinancingOrderStatus::ContractSigned]);
        $processOrder = new ProcessDmccContractSignedOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::CommoditySoldToCustomer));
    }
}
