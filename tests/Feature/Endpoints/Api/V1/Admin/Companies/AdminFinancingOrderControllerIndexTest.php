<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Companies;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class AdminFinancingOrderControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $sconedCompany;

    private static User $userLender;

    private static User $admin;

    private static User $manager;

    private static User $userBilling;

    private static User $userSupervisor;

    private static User $userOrderCrearor;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createCompany('2000', ['company_cr' => '12345678910'])[0];
        self::$sconedCompany = $this->createCompany('2000', ['company_cr' => '12345676666'])[0];
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$admin = $this->createLenderUser(self::$company->id, Role::Admin, 'admin@bim.com');
        self::$manager = $this->createLenderUser(self::$company->id, Role::Manager, 'Manager@bim.com');
        self::$userBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'LenderBilling@bim.com');
        self::$userSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'LenderSupervisor@bim.com');
        self::$userOrderCrearor = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'LenderOrderCreator@bim.com');

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

    public function test_admin_financing_order_controller_index_manager_can_access()
    {
        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_lender_can_not_access()
    {
        $this->actingAs(self::$userLender)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_billing_can_not_access()
    {
        $this->actingAs(self::$userBilling)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_supervisor_can_not_access()
    {
        $this->actingAs(self::$userSupervisor)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_order_creator_can_not_access()
    {
        $this->actingAs(self::$userOrderCrearor)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders')
            ->assertStatus(403);
    }
}
