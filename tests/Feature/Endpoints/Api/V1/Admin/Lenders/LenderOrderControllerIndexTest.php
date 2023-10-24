<?php

namespace Endpoints\Api\V1\Admin\Lenders;

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

class LenderOrderControllerIndexTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $lender;

    private static User $userLender;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermissionToIndexMethod;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();

        self::$managerHasPermissionToIndexMethod = $this->createSuperAdminUser();

        $this->assignPermissionToUser(
            self::$managerHasPermissionToIndexMethod,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Index])
        );

        self::$userLender = $this->createLenderUser(self::$lender->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$endpoint = 'api/v1/admin/orders';

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
    }

    public function test_admin_can_access_order_controller_index_successfully()
    {
        $this->actingAs(self::$admin)
            ->getJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(FinancingOrder::paginate(), (new FinancingOrderTransformer())->setArea(Area::SuperAdmin))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'company_name',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'current_step',
                        'status_reason',
                        'creator',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_manager_can_not_access_order_controller_index_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->getJson(self::$endpoint)
            ->assertStatus(403);
    }

    public function test_admin_manager_can_access_order_controller_index_when_has_permisson_successfully()
    {
        $this->actingAs(self::$managerHasPermissionToIndexMethod)
            ->getJson(self::$endpoint)
            ->assertStatus(200);
    }

    public function test_user_has_not_admin_roles_can_not_access_order_controller_index()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson(self::$endpoint);
        });
    }
}
