<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GetOrdersStatsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUser;
    use InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $userLenderOrderCreator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);

        // create order with different status
        FinancingOrder::factory(5)->create(['company_id' => self::$company->id, 'status' => FinancingOrderStatus::Completed]);
        FinancingOrder::factory(5)->create(['company_id' => self::$company->id, 'status' => FinancingOrderStatus::Cancelled]);
        FinancingOrder::factory(5)->create(['company_id' => self::$company->id, 'status' => FinancingOrderStatus::Rejected]);
        FinancingOrder::factory(5)->create(['company_id' => self::$company->id, 'status' => FinancingOrderStatus::Approved]);
        FinancingOrder::factory(5)->create(['company_id' => self::$company->id, 'status' => FinancingOrderStatus::PendingApproval]);

        FinancingOrder::factory(5)->create([
            'company_id' => self::$company->id,
            'status' => FinancingOrderStatus::Approved,
            'creator_type' => self::$userLenderOrderCreator->getMorphClass(),
            'creator_id' => self::$userLenderOrderCreator->getKey(),
        ]);

        FinancingOrder::factory(2)->create([
            'company_id' => self::$company->id,
            'status' => FinancingOrderStatus::Cancelled,
            'creator_type' => self::$userLenderOrderCreator->getMorphClass(),
            'creator_id' => self::$userLenderOrderCreator->getKey(),
        ]);
    }

    public function test_get_order_stats_successfully()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson('/api/v1/lender/orders/stats', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonFragment([
                'total_orders' => 32,
                'total_cancelled_orders' => 7,
                'total_active_orders' => 15,
                'total_require_action_orders' => 5,
                'total_completed_orders' => 5,
                'total_rejected_orders' => 5,
            ]);
    }

    public function test_orders_stats_by_creator()
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->getJson('/api/v1/lender/orders/stats', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonFragment([
                'total_orders' => 7,
                'total_active_orders' => 5,
                'total_require_action_orders' => 0,
                'total_cancelled_orders' => 2,
                'total_completed_orders' => 0,
                'total_rejected_orders' => 0,
            ]);
    }
}
