<?php

namespace Endpoints\Api\V1\Admin\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class OrderControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $lender;

    private static Company $secondLender;

    private static User $userLender;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermissionToIndexMethod;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();

        [self::$secondLender] = $this->createCompany();

        self::$managerHasPermissionToIndexMethod = $this->createSuperAdminUser();

        $this->assignPermissionToUser(
            self::$managerHasPermissionToIndexMethod,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Index])
        );

        self::$userLender = $this->createLenderUser(self::$lender->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);

        FinancingOrder::factory(5)->create([
            'company_id' => self::$lender->id,
            'approved_at' => Carbon::now(),
            'creator_id' => self::$userLender->id,
            'creator_type' => User::class,
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::InProgress,
            'is_verification_required' => true,
        ]);

        FinancingOrder::factory(5)->create([
            'company_id' => self::$secondLender->id,
            'approved_at' => Carbon::now(),
            'creator_id' => self::$userLender->id,
            'creator_type' => User::class,
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::InProgress,
            'is_verification_required' => true,
        ]);
    }

    public function test_admin_financing_order_controller_index_only_get_lender_orders()
    {
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/orders')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(FinancingOrder::paginate(), (new FinancingOrderTransformer())->setArea(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'status_reason',
                        'current_step',
                        'creator',
                        'charged_trader_orders_count',
                        'company_name',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_financing_order_controller_index_admin_can_access()
    {
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/orders')
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_manager_can_access_when_has_permisson()
    {
        $this->actingAs(self::$managerHasPermissionToIndexMethod)
            ->getJson('api/v1/admin/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/orders');
        });
    }
}
