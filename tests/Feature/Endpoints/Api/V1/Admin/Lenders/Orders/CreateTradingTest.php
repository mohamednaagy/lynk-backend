<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class CreateTradingTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLender;

    private static User $superAdminUser;

    private static User $managerHasPermissions;

    private static User $managerHasNoPermissionPermissions;

    private static Builder|Model $financingOrder;

    private static Builder|Model $traderOrder;

    private static string $apiUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$superAdminUser = $this->createSuperAdminUser();
        self::$managerHasPermissions = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasNoPermissionPermissions = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$managerHasPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
        );

        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'status' => FinancingOrderStatus::Approved,
            ]
        );

        self::$apiUrl = 'api/v1/admin/orders/'
            .self::$financingOrder->getRawOriginal('id').
            '/trading';
    }

    /**
     * @return void
     */
    public function test_create_trading_unauth_user_cant_make_order_completed(): void
    {
        $this->postJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_create_trading_only_roles_of_super_admin_area_can_access(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->postJson(self::$apiUrl, [
                    'trader' => 'fake',
                    'reference_number' => '102030',
                ]);
        });
    }

    public function test_create_trading_super_admin_can_access()
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
                'reference_number' => '102030',
            ])->assertStatus(Response::HTTP_OK);
    }

    public function test_create_trading_that_manager_with_permissions_can_access()
    {
        $this->actingAs(self::$managerHasPermissions)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
                'reference_number' => '102030',
            ])->assertStatus(Response::HTTP_OK);
    }

    public function test_create_trading_that_manager_without_permissions_can_not_access()
    {
        $this->actingAs(self::$managerHasNoPermissionPermissions)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
                'reference_number' => '102030',
            ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_create_trading_that_trader_is_required(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'reference_number' => '102030',
            ])
            ->assertJsonValidationErrorFor('trader');
    }

    /**
     * @return void
     */
    public function test_create_trading_reference_number_is_required(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
            ])
            ->assertJsonValidationErrorFor('reference_number');
    }

    /**
     * @return void
     */
    public function test_create_trading_trader_should_be_supported(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(
                self::$apiUrl,
                [
                    'trader' => 'wrong trader',
                    'reference_number' => '102030',
                ]
            )
            ->assertJsonValidationErrorFor('trader');
    }

    /**
     * @return void
     */
    public function test_create_trading_will_will_return_error_response_if_order_is_completed(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::Completed,
        ]);

        self::$financingOrder->refresh();

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
                'reference_number' => '102030',
            ])->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment([
                'message' => __('error.order_is_already_completed'),
            ]);
    }

    /**
     * @return void
     */
    public function test_create_trading_will_return_error_response_if_order_has_active_trader(): void
    {
        self::$financingOrder->traderOrders()->create(
            [
                'provider' => 'fake',
                'reference' => '102030',
                'status' => TraderOrderStatus::InProgress,
            ]
        );

        self::$financingOrder->refresh();

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
                'reference_number' => '102030',
            ])
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment([
                'message' => __('error.order_is_already_has_active_trader_order'),
            ]);
    }

    /**
     * @return void
     */
    public function test_create_trading_successfully(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$apiUrl, [
                'trader' => 'fake',
                'reference_number' => '102030',
            ])->assertStatus(Response::HTTP_OK);

        $this->assertTrue(self::$financingOrder->activeTraderOrder()->exists());
    }
}
