<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Users;

use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UserControllerUpdateTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Wallet $wallet;

    private static Company $otherCompany;

    private static Wallet $otherWallet;

    private static User $userTraderAdmin;

    private static User $otherUserTraderAdminOfSameCompany;

    private static User $otherUserTraderAdmin;

    private static array $traderDetails;

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
        self::$traderDetails = [
            'first_name' => 'trader',
            'last_name' => 'User',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'email' => 'traderUserEmail@bim.com',
            'redirect_url' => 'https://bimventures.com/:user',
            'role' => Role::TraderAdmin,
            'is_active' => 1,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_trader_user(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/users/'.self::$userTraderAdmin->id, self::$traderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_trader_area_cant_update_trader_user_case(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$company->id)
                    ->putJson('api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id, self::$traderDetails);
            }
        );
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_in_another_company(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/users/'.self::$otherUserTraderAdmin->id, self::$traderDetails)
            ->assertNotFound();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_update_trader_user_with_valid_data(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id, self::$traderDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_without_first_name(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['first_name'])
            )
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
    public function test_that_admin_user_cant_update_trader_user_without_last_name(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['last_name'])
            )
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The last name field is required.',
                'errors' => [
                    'last_name' => [
                        'The last name field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_without_phone_country_code(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['phone_country_code'])
            )
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not a valid phone number.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_without_phone_number(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['phone_number'])
            )
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The phone number field is required.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_without_email(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['email'])
            )
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The email field is required.',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_update_trader_user_without_redirect_url(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['redirect_url'])
            )
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_without_role(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['role'])
            )
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The role field is required.',
                'errors' => [
                    'role' => [
                        'The role field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_trader_user_without_is_active(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(
                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                Arr::except(self::$traderDetails, ['is_active'])
            )
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The is active field is required.',
                'errors' => [
                    'is_active' => [
                        'The is active field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_trader_admin_user_cant_update_trader_users_case_when_company_not_approved(): void
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
                ->putJson(
                    'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
                    self::$traderDetails
                )
                ->assertForbidden();
        }
    }

    /**
     * @return void
     */
//    public function test_that_trader_admin_user_cant_update_trader_user_case_email_not_verified(): void
//    {
//        self::$userTraderAdmin->update([
//            'email_verified_at' => null,
//        ]);
//
//        $this->actingAs(self::$userTraderAdmin)
//            ->withHeader('X-Company', self::$company->id)
//            ->putJson(
//                'api/v1/trader/users/'.self::$otherUserTraderAdminOfSameCompany->id,
//                self::$traderDetails
//            )
//            ->assertForbidden();
//    }
}
