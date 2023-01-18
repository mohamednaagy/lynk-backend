<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Users;

use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UserControllerShowTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Wallet $wallet;

    private static Company $otherCompany;

    private static Wallet $otherWallet;

    private static User $userTraderAdmin;

    private static User $otherUserTraderAdmin;

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
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_show_trader_user(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users/'.self::$userTraderAdmin->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_show_trader_user(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users/'.self::$userTraderAdmin->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$userTraderAdmin, new UserTransformer(Area::Trader))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_show_trader_user_in_other_company(): void
    {
        $this->actingAs(self::$userTraderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/users/'.self::$otherUserTraderAdmin->id)
            ->assertNotFound();
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
            ->getJson('api/v1/trader/users/'.self::$userTraderAdmin->id)
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
            ->getJson('api/v1/trader/users/'.self::$userTraderAdmin->id)
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
            ->getJson('api/v1/trader/users/'.self::$userTraderAdmin->id)
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
//            ->getJson('api/v1/trader/users/'.self::$userTraderAdmin->id)
//            ->assertForbidden();
//    }
}
