<?php

namespace Tests\Unit\Providers;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GateBeforeTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

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
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$order = $this->createOrder(self::$company->id, self::$userLenderOrderCreator->id);
        $this->withoutMiddleware([\Spatie\Permission\Middlewares\RoleMiddleware::class]);
    }

    public function test_gate_before_order_in_lender_area_only_lender_admin_can_access()
    {
        $rolesHasAccess = [
            Role::LenderSupervisor,
            Role::LenderAdmin,
            Role::LenderApiUser,
        ];

        $rolesDoesNotHasAccess = [
            Role::LenderBilling,
            Role::LenderOrderCreator,
        ];

        // user lender with order creator role
        // only can access when he is the creator of the order
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/orders/'.self::$order->id)
            ->assertStatus(Response::HTTP_OK);

        $this->assertStatusCodeToSpecificRoles(Response::HTTP_OK, $rolesHasAccess, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->getJson('api/v1/lender/orders/'.self::$order->id);
        });

        $this->assertStatusCodeToSpecificRoles(Response::HTTP_FORBIDDEN, $rolesDoesNotHasAccess, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->getJson('api/v1/lender/orders/'.self::$order->id);
        });

        $this->assertStatusCodeForAllRolesExceptForArea(Response::HTTP_FORBIDDEN, [Area::Lender], function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->getJson('api/v1/lender/orders/'.self::$order->id);
        });
    }

    public function test_gate_before_users_in_trader_area_only_trader_admin_can_access()
    {
        $this->actingAs(self::$traderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users')
            ->assertStatus(Response::HTTP_OK);

        $this->assertStatusCodeForAllRolesExceptForArea(Response::HTTP_FORBIDDEN, [Area::Trader], function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->getJson('api/v1/trader/users');
        });
    }

    public function test_gate_before_lenders_in_admin_area_only_super_admin_can_access()
    {
        $this->actingAs(self::$superAdmin)
            ->getJson('api/v1/admin/lenders')
            ->assertStatus(Response::HTTP_OK);

        $this->assertStatusCodeForAllRolesExceptForArea(Response::HTTP_FORBIDDEN, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/lenders');
        });

        $this->assertStatusCodeToSpecificRoles(Response::HTTP_FORBIDDEN, [Role::Manager], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/lenders');
        });
    }
}
