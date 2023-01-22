<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Auth;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GetAuthUserTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLender;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_fetch_his_details(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/auth')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_lender_can_fetch_his_details(): void
    {
        $data = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/auth')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userLender->load(['roles']), new UserTransformer(Area::Lender))
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
                        'company.id',
                        'permissions',
                        'locale',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()->getData(true)
            );
    }
}
