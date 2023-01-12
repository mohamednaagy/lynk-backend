<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader;

use App\Actions\Orders\GetPaginatedFinancingOrderAction;
use App\Enums\FinancingOrderHistory;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ListOrdersTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

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

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$companyTwo, self::$walletTwo] = $this->createCompany('2000', ['company_cr' => '12345678911']);
        self::$userTraderAdmin = $this->createLenderUser(self::$company->id, Role::TraderAdmin, 'traderAdmin@bim.com');
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
            ->getJson('api/v1/trader/orders')
            ->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_auth_user_with_proper_permission_can_access(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/orders')
            ->assertOk()
            ->assertExactJson(
                fractal((new GetPaginatedFinancingOrderAction())->setCompany(tenant())->handle(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'amount',
                        'selling_price',
                        'status',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_can_see_only_current_trader_company_orders(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/orders')
            ->assertOk()
            ->assertExactJson(
                fractal((new GetPaginatedFinancingOrderAction())->setCompany(tenant())->handle(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'amount',
                        'selling_price',
                        'status',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
