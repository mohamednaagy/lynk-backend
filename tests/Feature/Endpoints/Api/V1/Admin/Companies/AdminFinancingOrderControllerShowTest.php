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
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class AdminFinancingOrderControllerShowTest extends TestCase
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
    }

    public function test_admin_financing_order_controller_show_order_successed()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($order, new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                        'is_approved',
                        'status_reason',
                        'is_updatable',
                        'creator',
                        'approver',
                        'history',
                        'creator',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_financing_order_controller_show_order_not_found()
    {
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'. 400)
            ->assertStatus(404);
    }

    public function test_admin_financing_order_controller_show_admin_can_access()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();
        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_show_manager_can_not_access()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();
        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_manager_can_access_when_has_permisson()
    {
        Grantify::assignPermissionToModel(self::$manager, perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show]));
        $order = FinancingOrder::where('company_id', self::$company->id)->first();

        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(200);
    }

    public function test_admin_financing_order_controller_index_lender_can_not_access()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();

        $this->actingAs(self::$userLender)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_billing_can_not_access()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();

        $this->actingAs(self::$userBilling)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_supervisor_can_not_access()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();

        $this->actingAs(self::$userSupervisor)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(403);
    }

    public function test_admin_financing_order_controller_index_order_creator_can_not_access()
    {
        $order = FinancingOrder::where('company_id', self::$company->id)->first();

        $this->actingAs(self::$userOrderCrearor)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/orders/'.$order->id)
            ->assertStatus(403);
    }
}
