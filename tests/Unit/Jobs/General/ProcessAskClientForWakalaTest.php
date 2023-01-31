<?php

namespace Tests\Unit\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Sms\Events\SmsSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ProcessAskClientForWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static FinancingOrder $commoditySoldToCustomerOrder;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::CommoditySoldToCustomer,
        ]);

        self::$commoditySoldToCustomerOrder = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::CommoditySoldToCustomer,
        ]);
    }

    public function test_process_ask_client_for_wakala_processed_if_order_status_commodity_sold_to_customer()
    {
        $processOrder = new ProcessAskClientForWakala(self::$commoditySoldToCustomerOrder->id);

        $processOrder->handle();
        self::$commoditySoldToCustomerOrder = self::$commoditySoldToCustomerOrder->fresh();

        $this->assertTrue(self::$commoditySoldToCustomerOrder->status->is(FinancingOrderStatus::WaitingClientWakala));
    }

    public function test_process_ask_client_for_wakala_status_moved_to_waiting_client_wakala_successfully()
    {
        $processOrder = new ProcessAskClientForWakala(self::$order->id);

        $processOrder->handle();
        self::$order = self::$order->fresh();

        $this->assertTrue(self::$order->status->is(FinancingOrderStatus::WaitingClientWakala));
    }

    public function test_process_ask_client_for_wakala_will_not_processed_if_order_status_not_commodity_sold_to_customer()
    {
        $orderStatuses = FinancingOrderStatus::getValues();
        foreach ($orderStatuses as $orderStatus) {
            if ($orderStatus == FinancingOrderStatus::CommoditySoldToCustomer) {
                continue;
            }

            $order = $this->createOrder(self::$company->id, self::$lender->id, [
                'status' => $orderStatus,
            ]);
            $processOrder = new ProcessAskClientForWakala($order->id);
            $processOrder->handle();
            $order->refresh();

            $this->assertTrue($order->status->is($orderStatus));
        }
    }

    public function test_process_ask_client_for_wakala_will_not_processed_if_order_is_verification_required_false()
    {
        self::$commoditySoldToCustomerOrder->update(['is_verification_required' => false]);
        $processOrder = new ProcessAskClientForWakala(self::$commoditySoldToCustomerOrder->id);

        $processOrder->handle();
        self::$commoditySoldToCustomerOrder = self::$commoditySoldToCustomerOrder->fresh();

        $this->assertTrue(self::$commoditySoldToCustomerOrder->status->is(FinancingOrderStatus::WaitingClientWakala));
    }

    public function test_process_ask_client_for_wakala_sms_sent_successfully()
    {
        Event::fake([
            SmsSent::class,
        ]);

        $processOrder = new ProcessAskClientForWakala(self::$order->id);

        $processOrder->handle();

        Event::assertDispatched(SmsSent::class);
    }
}
