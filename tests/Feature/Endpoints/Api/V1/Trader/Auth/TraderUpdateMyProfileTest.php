<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Auth;

use App\Enums\Area;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderUpdateMyProfileTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static User $trader;

    private static Company $company;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createTraderCompany();
        self::$trader = $this->createTraderUser(self::$company->id);
    }

    /**
     * @return void
     */
    public function test_update_my_profile_un_auth_user_cant_update_update(): void
    {
        $this->putJson('api/v1/trader/auth/profile', [], ['X-Company' => self::$company->id])
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_update_my_profile_cant_update_without_first_name(): void
    {
        $this->actingAs(self::$trader)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/auth/profile', [
                'email' => 'test@bim.com',
                'last_name' => 'test name',
                'phone_number' => self::$trader->mobileDialingPhoneNumber,
                'phone_country_code' => self::$trader->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name');
    }

    public function test_update_my_profile_cant_update_without_last_name(): void
    {
        $this->actingAs(self::$trader)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/auth/profile', [
                'email' => 'test@bim.com',
                'first_name' => 'test name',
                'phone_number' => self::$trader->mobileDialingPhoneNumber,
                'phone_country_code' => self::$trader->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('last_name');
    }

    public function test_update_my_profile_cant_update_without_email(): void
    {
        $this->actingAs(self::$trader)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'phone_number' => self::$trader->mobileDialingPhoneNumber,
                'phone_country_code' => self::$trader->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_update_my_profile_cant_update_without_phone_number(): void
    {
        $this->actingAs(self::$trader)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/auth/profile', [
                'email' => 'test@bim.com',
                'first_name' => 'test name',
                'last_name' => 'test name',
                'phone_country_code' => self::$trader->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_number');
    }

    public function test_update_my_profile_cant_update_without_phone_country_code(): void
    {
        $this->actingAs(self::$trader)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/auth/profile', [
                'email' => 'test@bim.com',
                'first_name' => 'test name',
                'last_name' => 'test name',
                'phone_number' => self::$trader->mobileDialingPhoneNumber,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_country_code');
    }

    public function test_update_my_profile_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::Trader], function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->putJson('api/v1/trader/auth/profile', [
                    'email' => 'test@bim.com',
                    'first_name' => 'test name',
                    'last_name' => 'test name',
                    'phone_number' => self::$trader->mobileDialingPhoneNumber,
                    'phone_country_code' => self::$trader->phoneNumberCountryCode,
                ]);
        });
    }

    public function test_update_my_profile_updated_successfully(): void
    {
        $oldPassword = self::$trader->password;
        $this->actingAs(self::$trader)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/trader/auth/profile', [
                'email' => 'test@bim.com',
                'first_name' => 'test name',
                'last_name' => 'test name',
                'phone_number' => self::$trader->mobileDialingPhoneNumber,
                'phone_country_code' => self::$trader->phoneNumberCountryCode,
                'password' => 'random password',
                'password_confirmation' => 'random password',
            ])
            ->assertStatus(Response::HTTP_OK);

        self::$trader->fresh();

        $this->assertTrue(self::$trader->email == 'test@bim.com');
        $this->assertTrue(self::$trader->first_name == 'test name');
        $this->assertTrue(self::$trader->last_name == 'test name');
        $this->assertTrue(self::$trader->password != $oldPassword);
    }
}
