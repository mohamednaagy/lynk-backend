<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyMarketType;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Jobs\General\ProcessFinancingOrders;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class OrderControllerStoreTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

    private static Company $company;

    private static Company $company2;

    private static Wallet $wallet;

    private static Wallet $wallet2;

    private static User $userLenderAdmin;

    private static User $admin;

    private static User $mangerHasNoPermissions;

    private static User $managerHasPermissions;

    private static array $orderDetails;

    private static array $orderDetails2;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        [self::$company, self::$wallet] = $this->createCompany('2000');
        [self::$company2, self::$wallet2] = $this->createCompany('2000', ['does_order_require_approval' => false, 'require_initiate_trade_request' => false, 'preferred_market_type' => CompanyMarketType::International]);

        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$mangerHasNoPermissions = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermissions = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$managerHasPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Create])
        );

        self::$orderDetails = [
            'company_id' => self::$company->id,
            'customer_name' => 'youssof',
            'national_id' => '1001280070',
            'amount' => '200',
            'selling_price' => '220',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'is_verification_required' => true,
        ];

        self::$orderDetails2 = [
            'company_id' => self::$company2->id,
            'customer_name' => 'youssof',
            'national_id' => '1001280070',
            'amount' => '200',
            'selling_price' => '220',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'is_verification_required' => true,
        ];
    }

    public function test_that_un_auth_user_cant_create_order(): void
    {
        $this->postJson('api/v1/admin/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_national_id_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['national_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('national_id');
    }

    public function test_that_auth_user_without_company_id_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['company_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('company_id');
    }

    public function test_store_order_with_force_unique_reference_number(): void
    {
        self::$company->update(['force_unique_reference_number' => true]);

        $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::InProgress, 'reference_number' => '123']);

        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', array_merge(self::$orderDetails, ['reference_number' => '123']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('reference_number');
    }

    public function test_that_auth_user_without_amount_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['amount']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('amount')
            ->assertJsonValidationErrorFor('selling_price');
    }

    public function test_that_auth_user_without_selling_price_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['selling_price']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('selling_price');
    }

    public function test_that_auth_user_without_phone_country_code_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['phone_country_code']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_country_code')
            ->assertJsonValidationErrorFor('phone_number');
    }

    public function test_that_auth_user_without_phone_number_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['phone_number']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_number');
    }

    public function test_that_auth_user_without_is_verification_required_cant_create_order(): void
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', Arr::except(self::$orderDetails, ['is_verification_required']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('is_verification_required');
    }

    public function test_that_super_admin_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$admin)

            ->postJson('api/v1/admin/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'phone_country_code',
                    'phone_number',
                    'phone_number_formatted',
                    'id',
                    'status' => [
                        'description',
                        'value',
                    ],
                    'reference_number',
                    'national_id',
                    'amount',
                    'amount_formatted',
                    'selling_price',
                    'selling_price_formatted',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    public function test_that_manager_admin_without_permission_cant_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$mangerHasNoPermissions)
            ->postJson('api/v1/admin/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_manager_admin_with_permission_cant_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$managerHasPermissions)

            ->postJson('api/v1/admin/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'phone_country_code',
                    'phone_number',
                    'phone_number_formatted',
                    'id',
                    'status' => [
                        'description',
                        'value',
                    ],
                    'reference_number',
                    'national_id',
                    'amount',
                    'amount_formatted',
                    'selling_price',
                    'selling_price_formatted',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    public function test_create_hold_trader_when_bursa_in_cutting_time()
    {
        config()->set('services.bursam.market_opening_end_time', now(Config::get('services.bursam.timezone'))->subMinute()->toTimeString());
        config()->set('services.bursam.market_opening_start_time', now(Config::get('services.bursam.timezone'))->addMinute()->toTimeString());
        $response = $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', self::$orderDetails2)
            ->assertStatus(Response::HTTP_OK);
        $order = json_decode($response->getContent())->data;
        $this->assertEquals(FinancingOrderStatus::Approved, $order->status->value);
        $traderOrder = TraderOrder::where('financing_order_id', $order->id)->first();
        $this->assertEquals(TraderOrderStatus::Hold, $traderOrder->status->value);
    }

    public function test_handle_hold_trader_when_bursa_closing_time_is_outside_cutting_period()
    {
        config()->set('services.bursam.market_opening_end_time', now(Config::get('services.bursam.timezone'))->subMinute()->toTimeString());
        $response = $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/orders', self::$orderDetails2)
            ->assertStatus(Response::HTTP_OK);
        $order = json_decode($response->getContent())->data;
        $this->assertEquals(FinancingOrderStatus::Approved, $order->status->value);
        $traderOrder = TraderOrder::where('financing_order_id', $order->id)->first();
        $this->assertEquals(TraderOrderStatus::Hold, $traderOrder->status->value);
        config()->set('services.bursam.market_opening_start_time', now(Config::get('services.bursam.timezone'))->toTimeString());
        Queue::fake();
        Artisan::call('run:hold-bursa-traders');
        Queue::assertPushed(ProcessFinancingOrders::class);
        $this->assertEquals(TraderOrderStatus::Initiated, $traderOrder->refresh()->status->value);

    }
}
