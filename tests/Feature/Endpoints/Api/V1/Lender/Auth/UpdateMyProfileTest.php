<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Auth;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class UpdateMyProfileTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $approvedCompany;

    private static Company $notApprovedCompany;

    private static User $userLenderWithApprovedCompany;

    private static User $userLenderWithApprovedCompany1;

    private static User $notApprovedCompanyUserLender;

    private static User $emailNotVerifiedUserLender;

    private static User $userLenderApi;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$approvedCompany] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$notApprovedCompany] = $this->createCompany('2000', [
            'company_cr' => '12345678911',
            'status' => CompanyStatus::UnderReview,
        ]);
        self::$userLenderWithApprovedCompany = $this->createLenderUser(self::$approvedCompany->id, Role::LenderAdmin);
        self::$userLenderWithApprovedCompany1 = $this->createLenderUser(self::$approvedCompany->id, Role::LenderAdmin);
        self::$notApprovedCompanyUserLender = $this->createLenderUser(self::$notApprovedCompany->id, Role::LenderAdmin);
        self::$emailNotVerifiedUserLender = $this->createLenderUser(self::$approvedCompany->id, Role::LenderAdmin, [
            'email_verified_at' => null,
        ]);
        self::$userLenderApi = $this->createLenderUser(self::$approvedCompany->id, Role::LenderApiUser);
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_update_his_profile(): void
    {
        $this->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_lender_can_update_his_profile_without_updating_password(): void
    {
        $oldPassword = self::$userLenderWithApprovedCompany->password;
        $this->actingAs(self::$userLenderWithApprovedCompany)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'email' => self::$userLenderWithApprovedCompany->email,
                'phone_number' => self::$userLenderWithApprovedCompany->mobileDialingPhoneNumber,
                'phone_country_code' => self::$userLenderWithApprovedCompany->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userLenderWithApprovedCompany, new UserTransformer())
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()->getData(true)
            );
        $this->assertEquals($oldPassword, self::$userLenderWithApprovedCompany->password);
    }

    /**
     * @return void
     */
    public function test_lender_can_update_his_profile__with_updating_password(): void
    {
        $oldPassword = self::$userLenderWithApprovedCompany->password;
        $this->actingAs(self::$userLenderWithApprovedCompany)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'password' => '12345678$$',
                'email' => self::$userLenderWithApprovedCompany->email,
                'phone_number' => self::$userLenderWithApprovedCompany->mobileDialingPhoneNumber,
                'phone_country_code' => self::$userLenderWithApprovedCompany->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$userLenderWithApprovedCompany, new UserTransformer())
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                    ])->respond()->getData(true)
            );
        $this->assertNotEquals($oldPassword, self::$userLenderWithApprovedCompany->password);
    }

    /**
     * @return void
     */
    public function test_lender_cant_update_his_profile_when_company_not_approved(): void
    {
        $this->actingAs(self::$notApprovedCompanyUserLender)
            ->withHeader('X-Company', self::$notApprovedCompany->id)
            ->putJson('api/v1/lender/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'email' => self::$notApprovedCompanyUserLender->email,
                'phone_number' => self::$notApprovedCompanyUserLender->mobileDialingPhoneNumber,
                'phone_country_code' => self::$notApprovedCompanyUserLender->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson([
                'code' => 1015,
                'message' => __('error.company_not_active'),
            ]);
    }

    /**
     * @return void
     */
    public function test_lender_cant_update_his_profile_when_has_role_lender_api(): void
    {
        $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'email' => self::$userLenderApi->email,
                'phone_number' => self::$userLenderApi->mobileDialingPhoneNumber,
                'phone_country_code' => self::$userLenderApi->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_lender_cant_update_his_profile_when_email_not_verified(): void
    {
        $this->actingAs(self::$emailNotVerifiedUserLender)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'email' => self::$emailNotVerifiedUserLender->email,
                'phone_number' => self::$emailNotVerifiedUserLender->mobileDialingPhoneNumber,
                'phone_country_code' => self::$emailNotVerifiedUserLender->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson([
                'message' => __('error.must_verify_email'),
                'code' => 1008,
            ]);
    }

    /**
     * @return void
     */
    public function test_lender_update_his_profile_validation_rules(): void
    {
        $this->actingAs(self::$userLenderWithApprovedCompany)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile')
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name')
            ->assertJsonValidationErrorFor('email')
            ->assertJsonValidationErrorFor('phone_number')
            ->assertJsonValidationErrorFor('phone_country_code');

        $this->actingAs(self::$userLenderWithApprovedCompany)
            ->withHeader('X-Company', self::$approvedCompany->id)
            ->putJson('api/v1/lender/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'phone_number' => self::$userLenderWithApprovedCompany->mobileDialingPhoneNumber,
                'phone_country_code' => self::$userLenderWithApprovedCompany->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    }
}
