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

class UserControllerStoreTest extends TestCase
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
            'is_active' => 1,
        ];

        self::$endPoint = 'api/v1/admin/traders/'.self::$company->id.'/users';
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_store_trader_user(): void
    {
        $this->postJson(self::$endPoint, self::$userData)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_auth_admin_can_store_trader_user_with_valid_data(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endPoint, self::$userData)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                    'is_active',
                    'is_invitation_accepted',
                ],
            ]);
    }

    public function test_auth_admin_can_not_store_trader_user_with_invalid_data(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endPoint, Arr::except(self::$userData, ['first_name']))
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
    public function test_unauthorized_user_roles_can_store_trader_user(): void
    {
        $this->assertStatusCodeForAreaRoles(403, Area::Trader, function ($user, $role) {
            return $this->actingAs(self::$userTraderAdmin)
                ->postJson(self::$endPoint, self::$userData);
        });
    }

    /**
     * @return void
     */
    public function test_super_admin_roles_can_store_trader_user_successfully(): void
    {
        $this->assertStatusCodeForAreaRoles(200, Area::Trader, function ($user, $role) {
            return $this->actingAs(self::$userAdmin)
                ->postJson(self::$endPoint, self::$userData)
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'is_active',
                        'is_invitation_accepted',
                    ],
                ]);
        });
    }

    public function test_only_super_admin_roles_can_store_trader_users(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->postJson(self::$endPoint, self::$userData);
        });
    }
}
