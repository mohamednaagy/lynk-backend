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
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class LenderOrderControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;
    use InteractsWithAdmin;

    private static Company $lender;

    private static Company $secondLender;

    private static User $userLender;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermissionToIndexMethod;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345678910',
            ]
        );

        [self::$secondLender] = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345676666',
            ]
        );

        self::$managerHasPermissionToIndexMethod = $this->createManager(
            'ManagerHasPermission@bim.com',
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Index])
        );

        self::$userLender = $this->createLenderUser(self::$lender->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager('Manager@bim.com');

        FinancingOrder::factory(5)->create([
            'company_id' => self::$lender->id,
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
            'company_id' => self::$secondLender->id,
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

    public function test_admin_financing_order_controller_index_only_get_lender_orders()
    {
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/orders')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userLender->orders()->where('company_id', self::$lender->id)->paginate(), new FinancingOrderTransformer())
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
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/orders')
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_manager_can_access_when_has_permisson()
    {
        $this->actingAs(self::$managerHasPermissionToIndexMethod)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_other_roles_can_not_access()
    {
        $this->assertLenderUserCannotAccess(function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/orders');
        });
    }
}
