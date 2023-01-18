<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Users;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UserControllerStoreTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Wallet $wallet;

    private static Company $otherCompany;

    private static Wallet $otherWallet;

    private static User $userTraderAdmin;

    private static User $otherUserTraderAdmin;

    private static array $traderDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$otherCompany, self::$otherWallet] = $this->createCompany('2000', ['company_cr' => '12345678911']);
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id);
        self::$otherUserTraderAdmin = $this->createTraderUser(self::$otherCompany->id);
        self::$traderDetails = [
            'first_name' => 'trader',
            'last_name' => 'User',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'email' => 'traderUserEmail@bim.com',
            'redirect_url' => 'https://bimventures.com/:user',
            'role' => Role::TraderAdmin,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_store_trader_user(): void
    {
        Mail::fake();
        $this->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_store_trader_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', self::$traderDetails)
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
                    'role',
                ],
            ]);
        Mail::assertQueued(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_first_name(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['first_name']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The first name field is required.',
                'errors' => [
                    'first_name' => [
                        'The first name field is required.',
                    ],
                ],
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_last_name(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['last_name']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The last name field is required.',
                'errors' => [
                    'last_name' => [
                        'The last name field is required.',
                    ],
                ],
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_phone_country_code(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['phone_country_code']))
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
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_phone_number(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['phone_number']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The phone number field is required.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                ],
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_email(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['email']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The email field is required.',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                ],
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_redirect_url(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['redirect_url']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The redirect url field is required.',
                'errors' => [
                    'redirect_url' => [
                        'The redirect url field is required.',
                    ],
                ],
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_trader_user_without_role(): void
    {
        Mail::fake();
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', Arr::except(self::$traderDetails, ['role']))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The role field is required.',
                'errors' => [
                    'role' => [
                        'The role field is required.',
                    ],
                ],
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_trader_admin_user_cant_index_trader_users_case_company_pending(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Pending,
        ]);

        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', self::$traderDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_trader_admin_user_cant_index_trader_users_case_company_under_review(): void
    {
        self::$company->update([
            'status' => CompanyStatus::UnderReview,
        ]);

        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', self::$traderDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_trader_admin_user_cant_index_trader_users_case_company_rejected(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Rejected,
        ]);

        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/trader/users', self::$traderDetails)
            ->assertForbidden();
    }

//    /**
//     * @return void
//     */
//    public function test_that_trader_admin_user_cant_index_trader_users_case_email_not_verified(): void
//    {
//        self::$userTraderAdmin->update([
//            'email_verified_at' => null,
//        ]);
//
//        $this->actingAs(self::$userTraderAdmin)
//            ->withHeader('X-Company', self::$company->id)
//            ->postJson('api/v1/trader/users', self::$traderDetails)
//            ->assertForbidden();
//    }
}
