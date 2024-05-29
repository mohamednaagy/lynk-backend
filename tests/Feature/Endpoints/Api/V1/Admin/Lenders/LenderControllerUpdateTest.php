<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\CompanyMarketType;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderMode;
use App\Models\CommodityType;
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
use Tests\Traits\InteractsWithCommodityType;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderControllerUpdateTest extends TestCase
{
    use InteractsWithCommodityType, InteractsWithCompany, InteractsWithUser , RefreshDatabase;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static array $lenderDetails;

    private static string $endpoint;

    private static CommodityType $inactiveCommodityType;

    private static CommodityType $activeCommodityType;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', [
            'company_cr' => '1234567890',
            'unique_name' => 'companyUniqueNameTest',
        ]);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        self::$inactiveCommodityType = $this->createCommodityType(status: CommodityTypeStatus::Inactive);
        self::$activeCommodityType = $this->createCommodityType(status: CommodityTypeStatus::Active);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Edit])
        );

        self::$lenderDetails = [
            'name' => 'testCompany',
            'notifications_email' => 'notifications_email@email.com',
            'unique_name' => 'companyUniqueName',
            'company_cr' => '1234567891',
            'order_cost_tiers' => [
                [
                    'id' => null,
                    'order_value_start' => '0.00',
                    'order_value_end' => '100000',
                    'fee_type' => 'fixed',
                    'order_cost_without_vat' => '100',
                    'order_cost_with_vat' => '115',
                    'proration_amount' => null,
                ],
                [
                    'id' => null,
                    'order_value_start' => '100000.01',
                    'order_value_end' => 300000,
                    'fee_type' => 'fixed',
                    'order_cost_without_vat' => '80.00',
                    'order_cost_with_vat' => '92.00',
                    'proration_amount' => '20.00',
                ],
                [
                    'id' => null,
                    'order_value_start' => '300000.01',
                    'order_value_end' => null,
                    'fee_type' => 'proration',
                    'order_cost_without_vat' => '20.00',
                    'order_cost_with_vat' => '23.00',
                    'proration_amount' => 500000,
                ],
            ],
            'does_order_require_approval' => '1',
            'notify_admins_about_new_orders' => '1',
            'force_unique_reference_number' => '1',
            'notify_borrowers_about_order_updates' => '1',
            'require_initiate_trade_request' => '1',
            'webhook_secret_key' => Str::random(Config::get('webhook-server.secret_key_length', 40)),
            'trading_mode' => TraderOrderMode::Automatic,
            'preferred_market_type' => CompanyMarketType::International,
            'contract_number' => '1234567'.rand('111', '999'),
            'preferred_commodity_types' => [self::$activeCommodityType->id],

        ];
        self::$endpoint = 'api/v1/admin/lenders/';
    }

    public function test_un_auth_user_cant_update_lender(): void
    {
        $this->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_update_lender_successfully(): void
    {
        $commodity_type = $this->createCommodityType(status: CommodityTypeStatus::Active);
        self::$lenderDetails['preferred_commodity_types'] = [$commodity_type->id];
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->assertEquals(self::$lender->refresh()->unique_name, 'companyUniqueName');
        $this->assertEquals(self::$lender->refresh()->commodityTypes()->first()->id, $commodity_type->id);

    }

    public function test_manager_with_permissions_can_update_lender_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->assertEquals(self::$lender->refresh()->unique_name, 'companyUniqueName');
    }

    public function test_admin_can_update_lender_with_even_same_company_cr_and_unique_name_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(
                self::$endpoint.self::$lender->id,
                array_merge(
                    self::$lenderDetails,
                    [
                        'company_cr' => self::$lender->company_cr,
                        'unique_name' => self::$lender->unique_name,
                    ]
                )
            )
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    public function test_manager_without_permissions_cant_update_lender(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertForbidden();
    }

    public function test_admin_cant_update_lender_without_preferred_market_type(): void
    {
        self::$lenderDetails['preferred_commodity_types'] = [self::$inactiveCommodityType->id];
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The selected preferred_commodity_types.0 is invalid or inactive.',
                'errors' => [
                    'preferred_commodity_types.0' => [
                        'The selected preferred_commodity_types.0 is invalid or inactive.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_update_lender_with_invalid_preferred_commodity_type(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$lenderDetails, 'preferred_market_type'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The preferred market type field is required when trading mode is automatic.',
                'errors' => [
                    'preferred_market_type' => [
                        'The preferred market type field is required when trading mode is automatic.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_update_lender_with_invalid_preferred_market_type(): void
    {
        self::$lenderDetails['preferred_market_type'] = 55;
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The value you have entered is invalid.',
                'errors' => [
                    'preferred_market_type' => [
                        'The value you have entered is invalid.',
                    ],
                ],
            ]);
    }

    public function test_admin_cant_update_lender_without_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, Arr::except(self::$lenderDetails, 'name'))
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

    public function test_admin_can_update_lender_preferred_market_type_successfully(): void
    {
        self::$lenderDetails['preferred_market_type'] = CompanyMarketType::Any;
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
        $this->assertEquals(CompanyMarketType::Any, self::$lender->refresh()->preferred_market_type->value);

    }

    public function test_admin_can_update_lender_without_company_cr_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, Arr::except(self::$lenderDetails, 'company_cr'))
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    public function test_admin_cant_update_lender_without_does_order_require_approval(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, Arr::except(self::$lenderDetails, 'does_order_require_approval'))
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

    public function test_admin_cant_update_lender_without_order_cost(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, Arr::except(self::$lenderDetails, 'order_cost_tiers'))
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

    public function test_admin_cant_update_lender_without_unique_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, Arr::except(self::$lenderDetails, 'unique_name'))
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

    public function test_admin_cant_update_lender_without_trading_mode(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, Arr::except(self::$lenderDetails, 'trading_mode'))
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

    public function test_admin_cant_update_lender_with_exist_unique_name(): void
    {
        Company::query()->create(array_merge(self::$lenderDetails, [
            'status' => CompanyStatus::Approved(),
        ]));
        self::$lenderDetails['contract_number'] = '8528528522';

        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
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

    public function test_admin_can_update_lender_with_exist_unique_name_after_delete_successfully(): void
    {
        $lender = Company::query()->create(array_merge(self::$lenderDetails, [
            'status' => CompanyStatus::Approved(),
        ]));
        self::$lenderDetails['contract_number'] = '8528528522';

        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
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
            ->deleteJson(self::$endpoint.$lender->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
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
            ->putJson(self::$endpoint.self::$lender->id, self::$lenderDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);
    }
}
