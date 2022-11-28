<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Auth;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GetAuthUserTest extends TestCase
{
    use RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$company = Company::factory()->create([
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'companyName',
            'company_unique_name' => 'lynk05',
            'company_cr' => '12345678910',
        ]);

        self::$userLender = User::factory()->create([
            'email' => 'lender@bim.com',
            'password' => bcrypt('12345678'),
            'company_id' => self::$company->getOriginal('id'),
        ]);

        Grantify::assignRoleToModel(self::$userLender, Role::LenderAdmin);
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
