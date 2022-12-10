<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Companies;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class GetCompanyBalanceTest extends TestCase
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

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910', 'order_cost' => '200']);
        self::$userAdmin = $this->createLenderUser(self::$company->id, Role::Admin, 'admin@bim.com');
        self::$userManager = $this->createLenderUser(self::$company->id, Role::Manager, 'manager@bim.com');
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_get_company_balance(): void
    {
        $this->getJson('api/v1/admin/companies/'.self::$company->id.'/balance')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_get_company_balance(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_manager_can_get_company_balance(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/companies/'.self::$company->id.'/balance')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'available_orders' => '10',
                    'balance' => '20.00',
                ],
            ]);
    }
}
