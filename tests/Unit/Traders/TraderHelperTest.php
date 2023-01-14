<?php

namespace Tests\Unit\Traders;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\Traders\TraderHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class TraderHelperTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static object $traderHelper;

    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$traderHelper = $this->getObjectForTrait(TraderHelper::class);
    }

    public function test_trader_helper_create_trader_order()
    {
        self::$traderHelper->createTraderOrder(self::$financingOrder, '123', 'dmcc');

        self::assertEquals(1, self::$financingOrder->traderOrders()->count());
    }

    public function test_trader_helper_update_order_status()
    {
        self::$traderHelper->updateOrderStatus(self::$financingOrder, FinancingOrderStatus::Approved);

        self::assertTrue(self::$financingOrder->status->is(FinancingOrderStatus::Approved));
    }

    public function test_trader_helper_create_trader_order_history()
    {
        $traderOrder = self::$traderHelper->createTraderOrder(self::$financingOrder, '123', 'dmcc');
        self::$traderHelper->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        $this->assertEquals(1, $traderOrder->traderHistories()->count());
    }
}
