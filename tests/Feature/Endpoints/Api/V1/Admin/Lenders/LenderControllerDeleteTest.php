<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class LenderControllerDeleteTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createLenderUser(self::$company->id, Role::Admin, 'admin@bim.com');
        self::$userManager = $this->createLenderUser(self::$company->id, Role::Manager, 'manager@bim.com');
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_delete_companies(): void
    {
        $this->deleteJson('api/v1/admin/companies/'.self::$company->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_delete_companies(): void
    {
        $companiesCount = Company::query()->count();

        $this->actingAs(self::$userAdmin)
            ->deleteJson('api/v1/admin/companies/'.self::$company->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newCompaniesCount = Company::query()->count();

        $this->assertEquals($newCompaniesCount, $companiesCount - 1);
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_delete_companies(): void
    {
        $companiesCount = Company::query()->count();

        $this->actingAs(self::$userManager)
            ->deleteJson('api/v1/admin/companies/'.self::$company->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newCompaniesCount = Company::query()->count();

        $this->assertEquals($newCompaniesCount, $companiesCount - 1);
    }
}
