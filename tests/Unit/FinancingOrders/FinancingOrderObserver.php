<?php

namespace FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Enums\WebhookType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class FinancingOrderObserver extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany(2000, [
            'webhook_secret_key' => '123456',
        ]);
        self::$company->webhooks()->create([
            'url' => 'https://test.com',
            'type' => WebhookType::OrderUpdates,
        ]);
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::ContractSigned,
        ]);
        self::$order->traderOrders()->create([
            'provider' => config('trader.default'),
            'reference' => 123,
            'status' => TraderOrderStatus::InProgress,
            'product' => 'product test',
            'quantity' => 500,
        ]);
    }

    public function test_financing_order_observer_commodity_purchased_status()
    {
        Bus::fake();

        self::$order->update(['amount' => 250]);

        Bus::assertNotDispatched(CallWebhookJob::class);

        self::$order->update(['status' => FinancingOrderStatus::CommodityPurchased]);

        Bus::assertDispatched(CallWebhookJob::class);
    }
}
