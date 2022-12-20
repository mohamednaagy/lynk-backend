<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Subject;
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
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class LenderControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender, InteractsWithAdmin;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static array $companyDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createAdmin('admin@bim.com');
        self::$userManager = $this->createManager(
            'manager@bim.com',
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Edit]),
        );
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
    public function test_that_un_auth_user_cant_update_company(): void
    {
        $this->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_update_company(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->assertEquals(self::$company->refresh()->unique_name, 'companyUniqueName');
    }

    /**
     * @return void
     */
    public function test_that_manager_can_update_company(): void
    {
        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->assertEquals(self::$company->refresh()->unique_name, 'companyUniqueName');
    }

    /**
     * @return void
     */
    public function test_that_manager_without_permissions_cant_update_company(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_company_without_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, Arr::except(self::$companyDetails, 'name'))
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
    public function test_that_admin_can_update_company_without_company_cr(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, Arr::except(self::$companyDetails, 'company_cr'))
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_company_without_does_order_require_approval(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, Arr::except(self::$companyDetails, 'does_order_require_approval'))
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
    public function test_that_admin_cant_update_company_without_order_cost(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, Arr::except(self::$companyDetails, 'order_cost'))
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
    public function test_that_admin_cant_update_company_without_unique_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, Arr::except(self::$companyDetails, 'unique_name'))
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
    public function test_that_admin_cant_update_company_with_exist_unique_name(): void
    {
        Company::query()->create(array_merge(self::$companyDetails, [
            'status' => CompanyStatus::Approved(),
        ]));

        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
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
    public function test_that_admin_can_update_company_with_exist_unique_name_after_delete(): void
    {
        $company = Company::query()->create(array_merge(self::$companyDetails, [
            'status' => CompanyStatus::Approved(),
        ]));

        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
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
            ->deleteJson('api/v1/admin/companies/'.$company->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The company CR has already been taken.',
                'errors' => [
                    'company_cr' => [
                        'The company CR has already been taken.',
                    ],
                ],
            ]);

        $company->forceDelete();

        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id, self::$companyDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);
    }
}
