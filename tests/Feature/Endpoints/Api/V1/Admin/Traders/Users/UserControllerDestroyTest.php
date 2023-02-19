<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders\Users;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Modules\Grantify\Facades\Grantify;
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

    private static User $managerAdminUser;

    private static string $endpoint;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$userAdmin = $this->createSuperAdminUser();
        self::$managerAdminUser = $this->createSuperAdminUser(Role::Manager);
        [self::$company, self::$wallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678910']);
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id);
        self::$endpoint = 'api/v1/admin/traders/'.self::$company->id.'/users/'.self::$userTraderAdmin->id;
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_delete_trader_user_unsuccessful(): void
    {
        $this->deleteJson(self::$endpoint)
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
            ->deleteJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newTradersUserCount = User::query()->count();
        $this->assertEquals($newTradersUserCount, $tradersUserCount - 1);
    }

    /**
     * @return void
     */
    public function test_admin_manager_user_cant_delete_trader_user_with_permissions(): void
    {
        Grantify::syncPermissionToModel(self::$managerAdminUser, []);

        $this->actingAs(self::$managerAdminUser)
            ->deleteJson(self::$endpoint)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }

    /**
     * @return void
     */
    public function test_admin_manager_user_can_delete_trader_user_successful(): void
    {
        Grantify::assignPermissionToModel(
            self::$managerAdminUser,
            perm(Area::SuperAdmin, [Subject::TraderUsers, Action::Delete])
        );

        $this->actingAs(self::$managerAdminUser)
            ->deleteJson(self::$endpoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_user_cant_delete_trader_user_witout_trader_role_unsuccessful(): void
    {
        foreach (Area::roles() as $area => $roles) {
            if ($area === Area::Trader) {
                continue;
            }

            foreach ($roles as $role) {
                Grantify::syncRoleToModel(self::$userTraderAdmin, $role);

                $this->actingAs(self::$userAdmin)
                    ->deleteJson(self::$endpoint)
                    ->assertStatus(Response::HTTP_FORBIDDEN)
                    ->assertJsonPath('message', __('This action is unauthorized.'));
            }
        }
    }
}
