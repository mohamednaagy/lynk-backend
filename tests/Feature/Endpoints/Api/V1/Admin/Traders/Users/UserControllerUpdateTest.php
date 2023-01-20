<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders\Users;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCompany;

class UserControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, AssertsAccessByRoleAndArea;

    private static User $userAdmin;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userTraderAdmin;

    private static array $userData;

    private static string $endPoint;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        [self::$company, self::$wallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678910']);
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id);
        self::$userData = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_number' => '500112233',
            'phone_country_code' => 'SA',
            'email' => 'traderUser@bim.com',
            'redirect_url' => 'http://Lynk.com',
            'role' => Role::TraderAdmin,
        ];

        self::$endPoint = 'api/v1/admin/traders/'.self::$company->id.'/users/'.self::$userTraderAdmin->id;
    }

    /**
     * @return void
     */
    public function test_un_auth_user_can_not_update_trader_user(): void
    {
        $this->putJson(self::$endPoint, self::$userData)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_user_can_update_trader_user_with_valid_data(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endPoint, self::$userData)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    public function test_auth_admin_can_not_update_trader_user_with_invalid_data(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endPoint, Arr::except(self::$userData, ['first_name']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The first name field is required.',
                'errors' => [
                    'first_name' => [
                        'The first name field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_user_roles_can_update_trader_user(): void
    {
        $this->assertStatusCodeForAreaRoles(200, Area::Lender, function ($user, $role) {
            return $this->actingAs(self::$userAdmin)
                ->putJson(self::$endPoint, self::$userData)
                ->assertOk()
                ->assertExactJson([
                    'data' => [],
                ]);
        });
    }
}
