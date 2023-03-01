<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Area;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use App\Transformers\CompanyTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdmin;

    private static Company $trader;

    const Endpoint = 'api/v1/admin/traders';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [$lender] = $this->createLenderCompany(2000);
        [self::$trader] = $this->createTraderCompany(2000, ['driver' => 'fake']);

        self::$superAdmin = $this->createSuperAdminUser();

        $order = $this->createOrder(
            $lender->id,
            self::$superAdmin->id,
        );

        $anotherOrder = $this->createOrder(
            $lender->id,
            self::$superAdmin->id,
        );

        $anotherOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 2303,
        ]);

        $order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);

        $order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::Completed,
            'reference' => 123,
        ]);

        $order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::Cancelled,
            'reference' => 124,
        ]);

        $order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::Expired,
            'reference' => 125,
        ]);
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_index_un_auth_user_cant_index_companies(): void
    {
        $this->getJson(self::Endpoint)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_trader_company_controller_index()
    {
        $response = $this->actingAs(self::$superAdmin)
            ->getJson(self::Endpoint);

        $response->assertStatus(Response::HTTP_OK);

        self::$trader->setAttribute('orders_count', 1);

        $traders = new LengthAwarePaginator(
            [
                self::$trader,
            ],
            1,
            (new Company)->getPerPage()
        );

        $response->assertExactJson(
            fractal($traders, new CompanyTransformer())
                ->parseIncludes([
                    'id',
                    'name',
                    'status',
                    'unique_name',
                    'orders_count',
                ])
                ->respond()
                ->getData(true)
        );
    }

    public function test_trader_company_controller_index_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson(self::Endpoint);
        });
    }
}
