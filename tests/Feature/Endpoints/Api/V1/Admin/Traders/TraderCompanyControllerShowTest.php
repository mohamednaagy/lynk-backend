<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Actions\Contracts\Traders\GetOrdersAmountSumAndOrdersCountOfTrader;
use App\Enums\Area;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Transformers\CompanyTransformer;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerShowTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdmin;

    private static string $endpoint;

    private static Company $trader;

    private static Company $lender;

    private static FinancingOrder $order;

    private static FinancingOrder $anotherOrder;

    private static TraderOrder $traderOrder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createLenderCompany(2000);
        [self::$trader] = $this->createTraderCompany(2000, ['driver' => 'fake']);

        self::$superAdmin = $this->createSuperAdminUser();

        self::$order = $this->createOrder(
            self::$lender->id,
            self::$superAdmin->id,
            ['amount' => Money::parseByDecimal(1000.00, Money::getDefaultCurrency())]
        );

        self::$anotherOrder = $this->createOrder(
            self::$lender->id,
            self::$superAdmin->id,
            ['amount' => Money::parseByDecimal(1000.00, Money::getDefaultCurrency())]
        );

        self::$anotherOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 2303,
        ]);

        self::$anotherOrder->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 2303,
        ]);

        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::InProgress,
            'reference' => 123,
        ]);

        self::$traderOrder = self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::Completed,
            'reference' => 123,
        ]);

        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::Cancelled,
            'reference' => 124,
        ]);

        self::$order->traderOrders()->create([
            'provider' => 'fake',
            'status' => TraderOrderStatus::Expired,
            'reference' => 125,
        ]);

        self::$endpoint = 'api/v1/admin/traders/'.self::$trader->id;
    }

    /**
     * @return void
     */
    public function test_unauth_user_cant_access_trader_company_controller_show(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_access_trader_company_controller_show_successful()
    {
        $this->actingAs(self::$superAdmin)
            ->getJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK)
            ->assertOk();
    }

    public function test_other_user_has_not_role_in_super_admin_area_cant_access_trader_company_controller_show()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson(self::$endpoint);
        });
    }

    public function test_admin_can_access_trader_company_controller_show_with_valid_data_successful()
    {
        $traderFinancingOrdersQuery = FinancingOrder::query()
            ->whereHas('traderOrders', function ($query) {
                $query->where('provider', self::$trader->driver)
                    ->whereIn('status', TraderOrderStatus::$inProgressOrComplete);
            });

        $ordersAmountSumAndOrdersCountOfTrader = app(GetOrdersAmountSumAndOrdersCountOfTrader::class)->handle(self::$trader);
        self::$trader->setAttribute('orders_count', $ordersAmountSumAndOrdersCountOfTrader['ordersCount']);
        self::$trader->setAttribute('orders_sum_amount', $ordersAmountSumAndOrdersCountOfTrader['ordersSumAmount']);

        $response = $this->actingAs(self::$superAdmin)
            ->getJson(self::$endpoint);

        $response->assertExactJson(
            fractal(self::$trader, new CompanyTransformer())
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

        $totalAmount = (new Money(
            $traderFinancingOrdersQuery->sum('amount'),
            Money::getDefaultCurrency()
        ))
            ->formatByDecimal();

        $ordersSumAmountFormatted = number_format($totalAmount, 2);

        $response->assertJsonPath('data.orders_count', $traderFinancingOrdersQuery->count());
        $response->assertJsonPath('data.orders_sum_amount', $ordersSumAmountFormatted);
    }
}
