<?php

namespace Tests\Unit\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Enums\WebhookType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Sms\Events\SmsSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class FinancingOrderObserverTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createLenderCompany(2000, [
            'webhook_secret_key' => '123456',
        ]);
        self::$company->webhooks()->create([
            'url' => 'https://test.com',
            'type' => WebhookType::OrderUpdates,
        ]);
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::Approved,
        ]);
        self::$order->traderOrders()->create([
            'provider' => config('trader.default'),
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
            'product' => 'product test',
            'quantity' => 500,
        ]);
    }

    public function test_financing_order_observer_when_status_changes_to_commodity_sold_to_customer()
    {
        Bus::fake();

        Event::fake([
            SmsSent::class,
        ]);

        self::$order->update(['amount' => 250]);

        Bus::assertNotDispatched(CallWebhookJob::class);

        Event::assertNotDispatched(SmsSent::class);

        self::$order->update([
            'status' => FinancingOrderStatus::CommoditySoldToCustomer,
        ]);

        Bus::assertDispatched(CallWebhookJob::class);

        Event::assertDispatched(SmsSent::class);
    }

    public function test_financing_order_observer_when_status_changes_to_murabaha_sale_completed()
    {
        Event::fake([
            SmsSent::class,
        ]);

        self::$order->update(['amount' => 250]);

        Event::assertNotDispatched(SmsSent::class);

        self::$order->update(['status' => FinancingOrderStatus::MurabahaSaleCompleted]);

        Event::assertDispatched(SmsSent::class);
    }

    public function test_financing_order_observer_when_status_changes_to_commodity_purchased()
    {
        Bus::fake();

        self::$order->update(['amount' => 250]);

        Bus::assertNotDispatched(CallWebhookJob::class);

        self::$order->update(['status' => FinancingOrderStatus::CommodityPurchased]);

        Bus::assertDispatched(CallWebhookJob::class);
    }
}
