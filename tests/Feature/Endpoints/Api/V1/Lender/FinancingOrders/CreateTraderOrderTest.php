<?php

namespace Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class CreateTraderOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static string $apiUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$apiUrl = 'api/v1/lender/orders/'.self::$financingOrder->id.'/trader-orders';
    }

    public function test_that_un_auth_user_create_trader_order(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_create_trader_order_successfully()
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl)
            ->assertOk();

        $this->assertTrue(self::$financingOrder->activeTraderOrder()->exists());
    }

    public function test_create_trader_order_with_active_trader_order()
    {
        TraderOrder::create([
            'financing_order_id' => self::$financingOrder->id,
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_create_trader_order_fails_when_commodity_market_unavailable()
    {
        $timezone = Config::get('services.bursam.timezone');
        Config::set('trader.default', 'bursam');
        date_default_timezone_set(Config::get('services.bursam.timezone'));
        Config::set('services.bursam.market_opening_end_time', Carbon::now($timezone)->subHour()->format('H:i:s'));

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(self::$apiUrl)
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
