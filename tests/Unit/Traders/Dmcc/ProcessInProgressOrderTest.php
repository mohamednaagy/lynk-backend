<?php

namespace Tests\Unit\Traders\Dmcc;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\General\ProcessInProgressOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessInProgressOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected function setUp(): void
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
    public function test_process_in_progress_order_success()
    {
        $processOrder = new ProcessInProgressOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertNotNull(self::$order->traderOrders()->first());
        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::WaitingPurchasingCommodity));
    }

    /**
     * @throws \Throwable
     */
    public function test_process_in_progress_order_failed_with_not_valid_status()
    {
        $notValidStatuses = collect(FinancingOrderStatus::getValues())->filter(function ($status) {
            return ! in_array($status, [
                FinancingOrderStatus::Approved,
                FinancingOrderStatus::ClientWakalaCompleted,
            ]);
        });

        foreach ($notValidStatuses as $notValidStatus) {
            self::$order->update(['status' => $notValidStatus]);
            $processOrder = new ProcessInProgressOrder(self::$order->id);
            $processOrder->handle();
            self::$order = self::$order->fresh();

            $this->assertNull(self::$order->traderOrders()->first());
            $this->assertTrue(self::$order->status->is($notValidStatus));
        }
    }

    /**
     * @throws \Throwable
     */
    public function test_process_in_progress_order_failed_with_trader_order()
    {
        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
        ]);

        $processOrder = new ProcessInProgressOrder(self::$order->id);
        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::Approved));
    }
}
