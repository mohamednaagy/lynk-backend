<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders\Orders;

use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class TraderOrderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser, AssertsAccessByRoleAndArea;

    private static Company $traderCompany;

    private static Company $lenderCompany;

    private static User $userAdmin;

    private static Wallet $traderWallet;

    private static Wallet $lenderWallet;

    private static Builder|Model $order;

    private static Builder|Model $traderOrder;

    private static Builder|Model $traderHistory;

    private static string $baseURL;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$traderCompany, self::$traderWallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678910', 'driver' => 'fake']);
        [self::$lenderCompany, self::$lenderWallet] = $this->createLenderCompany('2000', ['company_cr' => '12345678911']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$order = $this->createOrder(self::$lenderCompany->id, self::$userAdmin->id);
        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);
        self::$traderHistory = self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetTtiId,
        ]);

        self::$baseURL = 'api/v1/admin/orders/'.self::$order->id;
    }

    /**
     * @return void
     */
    public function test_unauth_user_cant_access_order_controller_show(): void
    {
        $this->withHeader('X-Company', self::$traderCompany->id)
            ->getJson(self::$baseURL)
            ->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_admin_can_access_order_controller_show_successful(): void
    {
        $this->actingAs(self::$userAdmin)
            ->withHeader('X-Company', self::$traderCompany->id)
            ->getJson(self::$baseURL)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$order, new FinancingOrderTransformer(self::$traderCompany))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'is_updatable',
                        'is_approved',
                        'status_reason',
                        'approver',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                        'creator',
                        'created_at',
                        'trader_orders.id',
                        'trader_orders.reference',
                        'trader_orders.provider',
                        'trader_orders.is_cancellable',
                        'trader_orders.history',
                        'trader_orders.status',
                        'trader_orders.created_at',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_other_user_has_not_super_admin_area_cant_access_order_controller_show()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [Area::SuperAdmin],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$traderCompany->id)
                    ->getJson(self::$baseURL);
            }
        );
    }
}
