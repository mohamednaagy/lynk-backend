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

class LenderOrderControllerShowTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $lender;

    private static User $userLender;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermissionToShowMethod;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();

        self::$managerHasPermissionToShowMethod = $this->createSuperAdminUser(Role::Manager);

        $this->assignPermissionToUser(
            self::$managerHasPermissionToShowMethod,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show])
        );

        self::$userLender = $this->createLenderUser(self::$lender->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);

        FinancingOrder::factory(5)->create([
            'company_id' => self::$lender->id,
            'approved_at' => Carbon::now(),
            'creator_id' => self::$userLender->id,
            'creator_type' => User::class,
            'customer_name' => 'youssof',
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::InProgress,
            'is_verification_required' => true,
        ]);
        self::$endpoint = 'api/v1/admin/orders/';
    }

    public function test_admin_can_access_order_controller_show_successed()
    {
        $order = FinancingOrder::where('company_id', self::$lender->id)->first();
        $order->load([
            'creator',
            'traderOrders' => function ($query) {
                $query->latest('id');
            },
            'traderOrders.traderHistories',
        ]);

        $this->actingAs(self::$admin)
            ->getJson(self::$endpoint.$order->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($order, (new FinancingOrderTransformer())
                    ->setArea(Area::SuperAdmin)
                    ->setCurrentUser(self::$admin))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'customer_name',
                        'national_id',
                        'amount',
                        'selling_price',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                        'is_approved',
                        'is_cancellable',
                        'status_reason',
                        'can_be_completed',
                        'can_create_trader_order',
                        'is_updatable',
                        'approver',
                        'trader_orders.id',
                        'trader_orders.reference',
                        'trader_orders.provider',
                        'trader_orders.is_cancellable',
                        'trader_orders.history',
                        'trader_orders.products',
                        'trader_orders.status',
                        'trader_orders.created_at',
                        'creator',
                        'created_at',
                        'payment_proof_url',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_cant_access_order_controller_show_if_order_not_found()
    {
        $this->actingAs(self::$admin)
            ->getJson(self::$endpoint. 400)
            ->assertStatus(404);
    }

    public function test_admin_can_access_order_controller_show_successful()
    {
        $order = FinancingOrder::where('company_id', self::$lender->id)->first();
        $this->actingAs(self::$admin)
            ->getJson(self::$endpoint.$order->id)
            ->assertStatus(200);
    }

    public function test_admin_manager_cant_access_order_controller_show_with_no_permission()
    {
        $order = FinancingOrder::where('company_id', self::$lender->id)->first();
        $this->actingAs(self::$manager)
            ->getJson(self::$endpoint.$order->id)
            ->assertStatus(403);
    }

    public function test_admin_manager_can_access_order_controller_show_when_has_permission()
    {
        $order = FinancingOrder::where('company_id', self::$lender->id)->first();

        $this->actingAs(self::$managerHasPermissionToShowMethod)
            ->getJson(self::$endpoint.$order->id)
            ->assertStatus(200);
    }

    public function test_other_user_has_not_role_in_super_admin_area_cant_access_order_controller_show()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            $order = FinancingOrder::where('company_id', self::$lender->id)
                ->first();

            return $this->actingAs($user)
                ->getJson(self::$endpoint.$order->id);
        });
    }
}
