<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders\Users;

use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCompany;

class UserControllerDestroyTest extends TestCase
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
    public function test_un_auth_user_cant_delete_trader_user_unsuccessful(): void
    {
        $this->deleteJson(self::$endPoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_user_can_delete_trader_user_successful(): void
    {
        $tradersUserCount = User::query()->count();

        $this->actingAs(self::$userAdmin)
            ->deleteJson(self::$endPoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newTradersUserCount = User::query()->count();
        $this->assertEquals($newTradersUserCount, $tradersUserCount - 1);
    }
}
