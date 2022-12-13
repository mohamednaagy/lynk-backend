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

class LenderControllerIndexTest extends TestCase
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
    public function test_that_un_auth_user_cant_index_companies(): void
    {
        $this->getJson('api/v1/admin/companies')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_index_companies(): void
    {
        $companies = Company::query()->withCount('orders')->paginate();

        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/companies')
            ->assertOk()
            ->assertExactJson(
                fractal($companies, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'orders_count',
                        'created_at',
                        'order_cost',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_index_companies(): void
    {
        $companies = Company::query()->withCount('orders')->paginate();

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/companies')
            ->assertOk()
            ->assertExactJson(
                fractal($companies, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'orders_count',
                        'created_at',
                        'order_cost',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
