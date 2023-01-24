<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders\Orders;

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

class TraderOrderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userAdmin;

    private static Wallet $wallet;

    private static Builder|Model $order;

    private static Builder|Model $orderTwo;

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

        [self::$company, self::$wallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678910', 'type' => CompanyType::Trader]);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$order = $this->createOrder(self::$company->id, self::$userAdmin->id);
        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);
        self::$traderHistory = self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetTtiId,
        ]);

        self::$baseURL = 'api/v1/admin/traders/'.self::$company->id.'/orders/'.self::$order->id;
    }

    /**
     * @return void
     */
    public function test_unauth_user_cannot_access(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson(self::$baseURL)
            ->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_auth_user_with_proper_permission_can_access(): void
    {
        $this->actingAs(self::$userAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$baseURL)
            ->assertOk()
            ->assertExactJson(
                fractal((new GetOrderAction())->setCompany(self::$company)->handle(self::$order->id), new FinancingOrderTransformer(self::$company))
                    ->parseIncludes([
                        'id',
                        'company_id',
                        'company_name',
                        'status',
                        'amount',
                        'selling_price',
                        'created_at',
                    ])
                    ->respond()
                    ->getData(true)
            )->assertJsonCount(1);
    }

    public function test_trader_roles_only_can_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [Area::SuperAdmin],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->getJson(self::$baseURL);
            });
    }
}
