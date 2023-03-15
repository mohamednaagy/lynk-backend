<?php

namespace Tests\Unit\Models;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class TraderOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    protected FinancingOrder $financingOrder;

    protected TraderOrder $traderOrder;

    public function setUp(): void
    {
        parent::setUp();

        [$company] = $this->createLenderCompany();
        $user = $this->createLenderUser($company->id);

        $this->financingOrder = $this->createOrder($company->id, $user->id);

        $this->traderOrder = $this->financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => '1234',
        ]);
    }

    public function test_is_trader_order_cancellable()
    {
        $cancellable = collect(FinancingOrderHistory::asArray())
            ->values()
            ->reject(fn ($value) => is_array($value))
            ->reject(fn ($value) => in_array($value, FinancingOrderHistory::$notCancellableActions))
            ->map(fn ($action) => [
                'action' => $action,
            ])
            ->toArray();

        $notCancellable = collect(FinancingOrderHistory::$notCancellableActions)
            ->map(fn ($action) => [
                'action' => $action,
            ])
            ->values()
            ->toArray();

        $this->traderOrder->traderHistories()->createMany($notCancellable);
        $this->assertFalse($this->traderOrder->isCancellable());

        $this->traderOrder->traderHistories()->delete();

        $this->traderOrder->traderHistories()->createMany($cancellable);

        $this->traderOrder->load('traderHistories');

        $this->assertTrue($this->traderOrder->isCancellable());
    }
}
