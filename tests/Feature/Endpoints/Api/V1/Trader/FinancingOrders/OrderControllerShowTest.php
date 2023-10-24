<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\FinancingOrders;

use App\Actions\Orders\GetOrderAction;
use App\Enums\Area;
use App\Enums\CompanyType;
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

class OrderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Company $companyTwo;

    private static User $userTraderAdmin;

    private static Wallet $wallet;

    private static Wallet $walletTwo;

    private static Builder|Model $order;

    private static Builder|Model $orderTwo;

    private static Builder|Model $traderOrder;

    private static Builder|Model $traderHistory;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678910', 'type' => CompanyType::Trader, 'driver' => 'fake']);
        [self::$companyTwo, self::$walletTwo] = $this->createTraderCompany('2000', ['company_cr' => '12345678911', 'type' => CompanyType::Trader]);
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id);
        self::$order = $this->createOrder(self::$company->id, self::$userTraderAdmin->id);
        self::$orderTwo = $this->createOrder(self::$companyTwo->id, self::$userTraderAdmin->id);
        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);
        self::$traderHistory = self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetTtiId,
        ]);

        self::$endpoint = 'api/v1/trader/orders/';
    }

    public function test_un_auth_user_cannot_access_order_controller_show(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint.self::$order->id)
            ->assertUnauthorized();
    }

    public function test_trader_admin_with_proper_permission_can_access_order_controller_show_successful(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint.self::$order->id)
            ->assertOk()
            ->assertExactJson(
                fractal(
                    (new GetOrderAction())->setCompany(tenant())->handle(self::$order->id),
                    (new FinancingOrderTransformer())->setArea(Area::Trader)
                )
                    ->parseIncludes([
                        'id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'status',
                        'active_trader.id',
                        'active_trader.reference',
                        'active_trader.provider',
                        'active_trader.status',
                        'trader_orders.id',
                        'trader_orders.reference',
                        'trader_orders.provider',
                        'trader_orders.is_cancellable',
                        'trader_orders.history',
                        'trader_orders.products',
                        'trader_orders.status',
                        'trader_orders.created_at',
                    ])
                    ->respond()
                    ->getData(true)
            )->assertJsonCount(1);
    }

    public function test_trader_admin_cant_access_another_company_order(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint.self::$orderTwo->id)
            ->assertNotFound();
    }

    public function test_trader_roles_only_can_access_order_controller_show()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [Area::Trader],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->getJson(self::$endpoint.self::$company->id);
            }
        );
    }
}
