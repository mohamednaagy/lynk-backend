<?php

namespace Tests\Unit\Providers;

use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GateBeforeTest extends TestCase
{
    use InteractsWithUser, InteractsWithCompany;

    private static User $userLenderAdmin;

    private static User $userLenderOrderCreator;

    private static User $superAdmin;

    private static User $traderAdmin;

    private static Company $company;

    private static FinancingOrder $order;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createLenderCompany();
        self::$superAdmin = $this->createSuperAdminUser();
        self::$traderAdmin = $this->createTraderUser(self::$company->id);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$order = $this->createOrder(self::$company->id, self::$userLenderAdmin->id);
        $this->withoutMiddleware([\Spatie\Permission\Middlewares\RoleMiddleware::class]);
    }

    public function test_gate_before_order_in_trader_area_only_lender_admin_can_access()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/orders/'.self::$order->id)
            ->assertStatus(Response::HTTP_OK);

        $this->actingAs(self::$superAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/orders/'.self::$order->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs(self::$traderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/orders/'.self::$order->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_gate_before_users_in_trader_area_only_trader_admin_can_access()
    {
        $this->actingAs(self::$superAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users')
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users')
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs(self::$traderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users')
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_gate_before_lenders_in_admin_area_only_super_admin_can_access()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson('api/v1/admin/lenders')
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs(self::$traderAdmin)
            ->getJson('api/v1/admin/lenders')
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs(self::$superAdmin)
            ->getJson('api/v1/admin/lenders')
            ->assertStatus(Response::HTTP_OK);
    }
}
