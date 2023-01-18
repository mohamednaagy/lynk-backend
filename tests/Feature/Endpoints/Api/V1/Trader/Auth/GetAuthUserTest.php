<?php

namespace Endpoints\Api\V1\Trader\Auth;

use App\Enums\Area;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GetAuthUserTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userTrader;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910', 'type' => CompanyType::Trader]);
        self::$userTrader = $this->createTraderUser(self::$company->id);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_fetch_his_details(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/trader/auth')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_trader_user_can_fetch_his_details(): void
    {
        $this->assertStatusCodeForAreaRoles(Response::HTTP_OK, Area::Trader, function ($user) {
            return $this->actingAs(self::$userTrader)
                ->withHeader('X-Company', self::$company->id)
                ->getJson('api/v1/trader/auth')
                ->assertExactJson(
                    fractal(self::$userTrader->load(['roles']), new UserTransformer(Area::Trader))
                        ->parseIncludes([
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'is_email_verified',
                            'role',
                            'company.id',
                            'company.name',
                            'company.public_status_comment',
                            'company.status',
                            'permissions',
                            'locale',
                            'phone_number',
                            'phone_country_code',
                            'formatted_phone_number',
                        ])->respond()->getData(true)
                );
        });
    }
}
