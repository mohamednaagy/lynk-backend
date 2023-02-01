<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Users;

use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UserControllerDestroyTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Wallet $wallet;

    private static Company $otherCompany;

    private static Wallet $otherWallet;

    private static User $userTraderAdmin;

    private static User $otherUserTraderAdminOfSameCompany;

    private static User $otherUserTraderAdmin;

    private static String $endPoint;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678910']);
        [self::$otherCompany, self::$otherWallet] = $this->createTraderCompany('2000', ['company_cr' => '12345678911']);
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id);
        self::$otherUserTraderAdminOfSameCompany = $this->createTraderUser(self::$company->id);
        self::$otherUserTraderAdmin = $this->createTraderUser(self::$otherCompany->id);
        self::$endPoint = 'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id;
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_delete_trader_user_unsuccessful(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endPoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_other_area_roles_of_not_trader_area_cant_delete_trader_users_case_successful(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->deleteJson(self::$endPoint)
                    ->assertForbidden();
            }
        );
    }

    /**
     * @return void
     */
    public function test_trader_admin_user_can_delete_trader_user_successful(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endPoint)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_trader_admin_user_cant_delete_trader_user_in_other_company_unsuccessful(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/trader/users/'.self::$otherUserTraderAdmin->id)
            ->assertNotFound();
    }

    /**
     * @return void
     */
    public function test_trader_admin_user_cant_delete_trader_user_case_when_company_not_approved_unsuccessful(): void
    {
        foreach (CompanyStatus::getValues() as $status) {
            if ($status == CompanyStatus::Approved) {
                continue;
            }

            self::$company->update([
                'status' => $status,
            ]);

            $this->actingAs(self::$userTraderAdmin)
                ->withHeader('X-Company', self::$company->id)
                ->deleteJson(self::$endPoint)
                ->assertForbidden();
        }
    }
}
