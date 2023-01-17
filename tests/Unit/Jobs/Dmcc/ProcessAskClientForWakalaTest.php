<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Jobs\Dmcc\ProcessAskClientForWakala;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Sms\Events\SmsSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessAskClientForWakalaTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

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
            'status' => FinancingOrderStatus::Approved,

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
