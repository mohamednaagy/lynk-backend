<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Companies;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class CompanyControllerStore extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static array $companyDetails;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createLenderUser(self::$company->id, Role::Admin, 'admin@bim.com');
        self::$userManager = $this->createLenderUser(self::$company->id, Role::Manager, 'manager@bim.com');
        self::$companyDetails = [
            'name' => 'testCompany',
            'unique_name' => 'companyUniqueName',
            'company_cr' => '1234567891',
            'order_cost' => 20,
            'does_order_require_approval' => '1',
            'webhook_secret_key' => Str::random(Config::get('webhook-server.secret_key_length', 40)),
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_store_company(): void
    {
        $this->postJson('api/v1/admin/companies', self::$companyDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_store_company(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', self::$companyDetails)
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
                    'order_cost',
                ],
            ]);

        $company = Company::query()
            ->where('unique_name', 'companyUniqueName')
            ->first();

        $defaultStatus = $this->app->make(GetSettingsClassInstance::class)->handle(Area::Lender)
            ->default_company_status_created_by_operation;
        $hasWallet = $company->getWallets()->count() > 0;
        $hasOrderCost = $company->order_cost->getAmount() > 0;

        $this->assertEquals($defaultStatus, $company->status->value);
        $this->assertTrue($hasWallet);
        $this->assertTrue($hasOrderCost);
        $this->assertNotNull($company->webhook_secret_key);
    }

    /**
     * @return void
     */
    public function test_that_manager_can_store_company(): void
    {
        $this->actingAs(self::$userManager)
            ->postJson('api/v1/admin/companies', self::$companyDetails)
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
                    'order_cost',
                ],
            ]);

        $company = Company::query()
            ->where('unique_name', 'companyUniqueName')
            ->first();

        $defaultStatus = $this->app->make(GetSettingsClassInstance::class)->handle(Area::Lender)
            ->default_company_status_created_by_operation;
        $hasWallet = $company->getWallets()->count() > 0;
        $hasOrderCost = $company->order_cost->getAmount() > 0;

        $this->assertEquals($defaultStatus, $company->status->value);
        $this->assertTrue($hasWallet);
        $this->assertTrue($hasOrderCost);
        $this->assertNotNull($company->webhook_secret_key);
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_store_company_without_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', Arr::except(self::$companyDetails, 'name'))
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

    /**
     * @return void
     */
    public function test_that_admin_cant_store_company_without_company_cr(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', Arr::except(self::$companyDetails, 'company_cr'))
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

    /**
     * @return void
     */
    public function test_that_admin_cant_store_company_without_does_order_require_approval(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', Arr::except(self::$companyDetails, 'does_order_require_approval'))
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

    /**
     * @return void
     */
    public function test_that_admin_cant_store_company_without_order_cost(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', Arr::except(self::$companyDetails, 'order_cost'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The order cost field is required.',
                'errors' => [
                    'order_cost' => [
                        'The order cost field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_store_company_without_unique_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', Arr::except(self::$companyDetails, 'unique_name'))
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

    /**
     * @return void
     */
    public function test_that_admin_cant_store_company_with_exist_unique_name(): void
    {
        Company::query()->create(array_merge(self::$companyDetails, [
            'status' => CompanyStatus::Approved(),
        ]));

        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', self::$companyDetails)
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

    /**
     * @return void
     */
    public function test_that_admin_can_store_company_with_exist_unique_name_after_delete(): void
    {
        $company = Company::query()->create(array_merge(self::$companyDetails, [
            'status' => CompanyStatus::Approved(),
        ]));

        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', self::$companyDetails)
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

        $company->forceDelete();

        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/companies', self::$companyDetails)
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
                    'order_cost',
                ],
            ]);
    }
}
