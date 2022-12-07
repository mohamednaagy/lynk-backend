<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Users;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class UserControllerUpdate extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderApi;

    private static User $userLenderOrderCreator;

    private static array $lenderDetails;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'firstLenderAdmin@bim.com');
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'lenderSupervisor@bim.com');
        self::$userLenderApi = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'lenderApi@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'lenderBilling@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'lenderOrderCreator@bim.com');
        self::$lenderDetails = [
            'first_name' => 'Lender',
            'last_name' => 'User',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'email' => 'lenderUserEmail@bim.com',
            'redirect_url' => 'https://bimventures.com/',
            'role' => Role::LenderAdmin,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_lender_user(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderAdmin->id, self::$lenderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, self::$lenderDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_lender_user_without_first_name(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['first_name']))
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
    public function test_that_admin_user_cant_update_lender_user_without_last_name(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['last_name']))
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
    public function test_that_admin_user_cant_update_lender_user_without_phone_country_code(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['phone_country_code']))
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
    public function test_that_admin_user_cant_update_lender_user_without_phone_number(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['phone_number']))
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
    public function test_that_admin_user_cant_update_lender_user_without_email(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['email']))
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
    public function test_that_admin_user_can_update_lender_user_without_redirect_url(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['redirect_url']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_lender_user_without_role(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderSupervisor->id, Arr::except(self::$lenderDetails, ['role']))
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
    public function test_that_supervisor_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderBilling->id, Arr::except(self::$lenderDetails, ['redirect_url']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_billing_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderOrderCreator->id, Arr::except(self::$lenderDetails, ['redirect_url']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderBilling->id, Arr::except(self::$lenderDetails, ['redirect_url']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_api_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderBilling->id, Arr::except(self::$lenderDetails, ['redirect_url']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_update_api_user_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/users/'.self::$userLenderApi->id, Arr::except(self::$lenderDetails, ['redirect_url']))
            ->assertForbidden();
    }
}
