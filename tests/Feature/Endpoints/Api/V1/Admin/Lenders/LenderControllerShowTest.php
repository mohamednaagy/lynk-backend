<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\CompanyTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class LenderControllerShowTest extends TestCase
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
    public function test_that_un_auth_user_cant_show_company(): void
    {
        $this->getJson('api/v1/admin/companies/'.self::$company->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_show_company(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/companies/'.self::$company->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$company, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'order_cost',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_index_company(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/companies/'.self::$company->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$company, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'order_cost',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
