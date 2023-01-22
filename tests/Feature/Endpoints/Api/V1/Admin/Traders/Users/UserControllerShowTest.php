<?php

namespace Endpoints\Api\V1\Admin\Traders\Users;

use App\Enums\Area;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCompany;

class UserControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, AssertsAccessByRoleAndArea;

    private static User $userAdmin;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userTraderAdmin;

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
        self::$endPoint = 'api/v1/admin/traders/'.self::$company->id.'/users/'.self::$userTraderAdmin->id;
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_show_trader_user(): void
    {
        $this->getJson(self::$endPoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_user_can_show_trader_user(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endPoint)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$userTraderAdmin, new UserTransformer(Area::Trader))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'role',
                    ])->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_super_admin_roles_can_show_trader_user(): void
    {
        $this->assertStatusCodeForAreaRoles(200, Area::Trader, function ($user, $role) {
            return $this->actingAs(self::$userAdmin)
                ->getJson(self::$endPoint)
                ->assertExactJson(
                    fractal(self::$userTraderAdmin, new UserTransformer(Area::Trader))
                        ->parseIncludes([
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'phone_number',
                            'phone_country_code',
                            'formatted_phone_number',
                            'role',
                        ])->respond()
                        ->getData(true)
                );
        });
    }

    public function test_only_super_admin_roles_can_show_trader_users(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson(self::$endPoint);
        });
    }
}
