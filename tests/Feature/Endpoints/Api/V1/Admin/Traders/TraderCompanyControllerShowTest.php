<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Actions\Contracts\Traders\ShowTrader;
use App\Enums\Area;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Transformers\CompanyTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerShowTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdmin;

    private static Company $company;

    private static FinancingOrder $order;

    private static TraderOrder $traderOrder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createTraderCompany(2000);

        self::$superAdmin = $this->createSuperAdminUser();

        self::$order = $this->createOrder(self::$company->id, self::$superAdmin->id);

        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_show_un_auth_user_cant_show_company(): void
    {
        $this->getJson('api/v1/admin/traders/'.self::$company->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_trader_company_controller_show_successful()
    {
        $this->actingAs(self::$superAdmin)
            ->getJson('api/v1/admin/traders/'.self::$company->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertOk();
    }

    public function test_trader_company_controller_show_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/traders/'.self::$company->id);
        });
    }

    public function test_trader_company_controller_show_succeed()
    {
        $loadRelationsForTrader = app(ShowTrader::class)->handle(self::$company);

        $this->actingAs(self::$superAdmin)
            ->getJson('api/v1/admin/traders/'.self::$company->id)
            ->assertExactJson(
                fractal($loadRelationsForTrader, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'driver',
                        'orders_count',
                        'orders_sum_amount',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
