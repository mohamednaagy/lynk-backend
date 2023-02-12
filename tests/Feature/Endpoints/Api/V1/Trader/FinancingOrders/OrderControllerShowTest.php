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

    /**
     * @return void
     *
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
    }

    /**
     * @return void
     */
    public function test_unauth_user_cannot_access(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/orders/'.self::$order->id)
            ->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_auth_user_with_proper_permission_can_access(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/orders/'.self::$order->id)
            ->assertOk()
            ->assertExactJson(
                fractal((new GetOrderAction())->setCompany(tenant())->handle(self::$order->id), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'amount',
                        'selling_price',
                        'status',
                        'active_trader.id',
                        'active_trader.reference',
                        'active_trader.provider',
                        'active_trader.status',
                        'trader_order_history',
                    ])
                    ->respond()
                    ->getData(true)
            )->assertJsonCount(1);
    }

    /**
     * @return void
     */
    public function test_can_see_only_current_trader_company_orders(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/orders/'.self::$orderTwo->id)
            ->assertNotFound();
    }

    public function test_trader_roles_only_can_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [Area::Trader],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->getJson('api/v1/trader/orders/'.self::$company->id);
            });
    }
}
