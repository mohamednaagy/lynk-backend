<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderMode;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderControllerStoreTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static array $standardLenderDetails;

    private static array $tieredLenderDetails;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Create])
        );

        self::$standardLenderDetails = [
            'name' => 'testCompany',
            'notifications_email' => 'notifications_email@email.com',
            'unique_name' => 'companyUniqueName',
            'company_cr' => '1234567891',
            'order_cost_tiers' => [
                [
                    'id' => null,
                    'order_value_start' => '0.00',
                    'order_value_end' => null,
                    'fee_type' => 'fixed',
                    'order_cost_without_vat' => '100',
                    'order_cost_with_vat' => '115',
                    'proration_amount' => null,
                ],
            ],
            'does_order_require_approval' => '1',
            'notify_borrowers_about_order_updates' => '1',
            'require_initiate_trade_request' => '1',
            'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::On,
            'webhook_secret_key' => Str::random(Config::get('webhook-server.secret_key_length', 40)),
            'trading_mode' => TraderOrderMode::Automatic,
        ];
        self::$endpoint = 'api/v1/admin/lenders';
    }

    public function test_un_auth_user_cant_store_lender(): void
    {
        $this->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_store_lender_with_Standard_order_cost_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'status',
                    'created_at',
                    'unique_name',
                    'company_cr',
                    'does_order_require_approval',
                    'require_initiate_trade_request',
                    'notify_borrowers_about_order_updates',
                ],
            ]);

        $lender = Company::query()
            ->where('unique_name', 'companyUniqueName')
            ->first();
        $defaultStatus = $this->app->make(GetSettingsClassInstance::class)->handle(Area::Lender)
            ->default_company_status_created_by_operation;
        $hasWallet = $lender->getWallets(WalletType::CompanyWallet)->count() > 0;

        $orderCost = $lender->tieredPricing()->first()?->order_cost_without_vat->getAmount();

        $hasOrderCost = $orderCost > 0;
        $isOrderCostCorrect = ($orderCost === (string) (self::$standardLenderDetails['order_cost_tiers'][0]['order_cost_without_vat'] * 100));

        $this->assertEquals($defaultStatus, $lender->status->value);
        $this->assertTrue($hasWallet);
        $this->assertTrue($hasOrderCost);
        $this->assertTrue($isOrderCostCorrect);
        $this->assertNotNull($lender->webhook_secret_key);
    }

    public function test_manager_with_permissions_can_store_lender_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'status',
                    'created_at',
                    'unique_name',
                    'company_cr',
                    'does_order_require_approval',
                ],
            ]);

        $lender = Company::query()
            ->where('unique_name', 'companyUniqueName')
            ->first();

        $defaultStatus = $this->app->make(GetSettingsClassInstance::class)->handle(Area::Lender)
            ->default_company_status_created_by_operation;
        $hasWallet = $lender->getWallets(WalletType::CompanyWallet)->count() > 0;
        $orderCost = $lender->tieredPricing()->first()?->order_cost_without_vat->getAmount();

        $hasOrderCost = $orderCost > 0;
        $isOrderCostCorrect = ($orderCost === (string) (self::$standardLenderDetails['order_cost_tiers'][0]['order_cost_without_vat'] * 100));

        $this->assertEquals($defaultStatus, $lender->status->value);
        $this->assertTrue($hasWallet);
        $this->assertTrue($hasOrderCost);
        $this->assertTrue($isOrderCostCorrect);
        $this->assertNotNull($lender->webhook_secret_key);
    }

    public function test_manager_without_permissions_cant_store_lender(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertForbidden();
    }

    public function test_admin_cant_store_lender_without_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$standardLenderDetails, 'name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The name field is required.',
                'errors' => [
                    'name' => [
                        'The name field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_store_lender_without_company_cr(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$standardLenderDetails, 'company_cr'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The company CR field is required.',
                'errors' => [
                    'company_cr' => [
                        'The company CR field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_store_lender_without_does_order_require_approval(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$standardLenderDetails, 'does_order_require_approval'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The does order require approval field is required.',
                'errors' => [
                    'does_order_require_approval' => [
                        'The does order require approval field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_store_lender_without_order_cost(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$standardLenderDetails, 'order_cost_tiers'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The order cost tiers field is required. (and 1 more error)',
                'errors' => [
                    'order_cost_tiers' => [
                        'The order cost tiers field is required.',
                    ],
                    'order_cost_tiers.0.order_value_start' => [
                        'The order cost tiers.0.order value start field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_store_lender_without_unique_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$standardLenderDetails, 'unique_name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The unique name field is required.',
                'errors' => [
                    'unique_name' => [
                        'The unique name field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_store_lender_without_trading_mode(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$standardLenderDetails, 'trading_mode'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The trading mode field is required.',
                'errors' => [
                    'trading_mode' => [
                        'The trading mode field is required.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_store_lender_with_exist_unique_name(): void
    {
        Company::query()->create(array_merge(self::$standardLenderDetails, [
            'status' => CompanyStatus::Approved(),
        ]));

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The unique name has already been taken. (and 1 more error)',
                'errors' => [
                    'unique_name' => [
                        'The unique name has already been taken.',
                    ],
                    'company_cr' => [
                        'The company CR has already been taken.',
                    ],
                ],
            ]);
    }

    public function test_admin_can_store_lender_with_exist_unique_name_after_delete_successfully(): void
    {
        $lender = Company::query()->create(array_merge(self::$standardLenderDetails, [
            'status' => CompanyStatus::Approved(),
        ]));

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The unique name has already been taken. (and 1 more error)',
                'errors' => [
                    'unique_name' => [
                        'The unique name has already been taken.',
                    ],
                    'company_cr' => [
                        'The company CR has already been taken.',
                    ],
                ],
            ]);
        $this->actingAs(self::$userAdmin)
            ->deleteJson('api/v1/admin/lenders/'.$lender->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id, self::$standardLenderDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The company CR has already been taken.',
                'errors' => [
                    'company_cr' => [
                        'The company CR has already been taken.',
                    ],
                ],
            ]);

        $lender->forceDelete();

        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$standardLenderDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'status',
                    'created_at',
                    'unique_name',
                    'company_cr',
                    'does_order_require_approval',
                ],
            ]);
    }
}
