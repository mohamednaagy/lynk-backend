<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Companies;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class AdminFinancingOrderControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Company $sconedCompany;

    private static User $userLender;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermisionToIndexMethod;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();

        [self::$sconedCompany] = $this->createCompany();

        self::$managerHasPermisionToIndexMethod = $this->createSuperAdminUser();

        $this->assignPermissionToUser(
            self::$managerHasPermisionToIndexMethod,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Index])
        );

        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);

        FinancingOrder::factory(5)->create([
            'company_id' => self::$company->id,
            'approved_at' => Carbon::now(),
            'creator_id' => self::$userLender->id,
            'creator_type' => User::class,
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::WaitingClientWakala,
            'is_verification_required' => true,
        ]);

        FinancingOrder::factory(5)->create([
            'company_id' => self::$sconedCompany->id,
            'approved_at' => Carbon::now(),
            'creator_id' => self::$userLender->id,
            'creator_type' => User::class,
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::WaitingClientWakala,
            'is_verification_required' => true,
        ]);
    }

    public function test_admin_financing_order_controller_index_only_get_company_orders()
    {
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userLender->orders()->where('company_id', self::$company->id)->paginate(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'status_reason',
                        'creator',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_financing_order_controller_index_admin_can_access()
    {
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_manager_can_access_when_has_permisson()
    {
        $this->actingAs(self::$managerHasPermisionToIndexMethod)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin, Area::Customer], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders');
        });
    }
}
