<?php

namespace Models;

use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FinancingOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    protected CommittedOrder $financingOrder;

    protected TraderOrder $traderOrder;

    public function setUp(): void
    {
        parent::setUp();

        [$company] = $this->createLenderCompany();
        $user = $this->createLenderUser($company->id);

        $this->financingOrder = OrderScenario::inProgress()
            ->lender($company)
            ->creator($user)
            ->commit();

        $this->traderOrder = InProgressOrder::of($this->financingOrder)->createTraderOrder();
    }

    public function test_cannot_create_trader_order_after_has_completed_trader_order()
    {
        TraderOrderScenario::of($this->traderOrder)->moveToStep(MurabhaStep::MurabahaSaleCompleted);

        $this->assertFalse($this->financingOrder->model()->canCreateTraderOrder());
    }
}
