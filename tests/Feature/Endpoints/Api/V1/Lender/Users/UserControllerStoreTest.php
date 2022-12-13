<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Users;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class UserControllerStoreTest extends TestCase
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
    public function test_that_un_auth_user_cant_store_lender_user(): void
    {
        Mail::fake();
        $this->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
        Mail::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_store_lender_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
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
        Mail::assertSent(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_lender_user_without_first_name(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['first_name']))
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
    public function test_that_admin_user_cant_store_lender_user_without_last_name(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['last_name']))
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
    public function test_that_admin_user_cant_store_lender_user_without_phone_country_code(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['phone_country_code']))
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
    public function test_that_admin_user_cant_store_lender_user_without_phone_number(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['phone_number']))
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
    public function test_that_admin_user_cant_store_lender_user_without_email(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['email']))
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
    public function test_that_admin_user_cant_store_lender_user_without_redirect_url(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['redirect_url']))
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
    public function test_that_admin_user_cant_store_lender_user_without_role(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', Arr::except(self::$lenderDetails, ['role']))
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
    public function test_that_supervisor_user_cant_store_lender_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
        Mail::assertNotSent(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_billing_user_cant_store_lender_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
        Mail::assertNotSent(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_cant_store_lender_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
        Mail::assertNotSent(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_api_user_cant_store_lender_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
        Mail::assertNotSent(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_cant_store_api_user_with_valid_data(): void
    {
        Mail::fake();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', array_merge(self::$lenderDetails, ['role' => Role::LenderApiUser]))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The selected role is invalid.',
                'errors' => [
                    'role' => [
                        'The selected role is invalid.',
                    ],
                ],
            ]);
        Mail::assertNotSent(CompleteRegisterInvitation::class);
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_company_pending(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Pending,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_company_under_review(): void
    {
        self::$company->update([
            'status' => CompanyStatus::UnderReview,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_company_rejected(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Rejected,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_email_not_verified(): void
    {
        self::$userLenderAdmin->update([
            'email_verified_at' => null,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/users', self::$lenderDetails)
            ->assertForbidden();
    }
}
