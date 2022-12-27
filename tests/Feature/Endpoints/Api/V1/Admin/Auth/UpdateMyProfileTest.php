<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Auth;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;

class UpdateMyProfileTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin;

    private static User $adminUser;

    private static mixed $getSettingsClassInstance;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$getSettingsClassInstance = app(GetSettingsClassInstance::class);
        self::$adminUser = $this->createAdmin('admin@bim.com');
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_update_his_profile(): void
    {
        $this->putJson('api/v1/admin/auth/profile')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_can_update_his_profile_without_updating_password(): void
    {
        $oldPassword = self::$adminUser->password;
        $oldFirstName = self::$adminUser->first_name;
        $oldLastName = self::$adminUser->last_name;
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'email' => self::$adminUser->email,
                'phone_number' => self::$adminUser->mobileDialingPhoneNumber,
                'phone_country_code' => self::$adminUser->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);

        $this->assertEquals($oldPassword, self::$adminUser->password);
        $this->assertNotEquals($oldFirstName, self::$adminUser->first_name);
        $this->assertNotEquals($oldLastName, self::$adminUser->last_name);
    }

    /**
     * @return void
     */
    public function test_admin_can_update_his_profile_with_updating_password(): void
    {
        $oldPassword = self::$adminUser->password;
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'password' => '12345678$$',
                'password_confirmation' => '12345678$$',
                'email' => self::$adminUser->email,
                'phone_number' => self::$adminUser->mobileDialingPhoneNumber,
                'phone_country_code' => self::$adminUser->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);

        $this->assertNotEquals($oldPassword, self::$adminUser->password);
    }

    /**
     * @return void
     */
    public function test_admin_can_update_his_profile_when_has_manager_role(): void
    {
        Grantify::syncRoleToModel(self::$adminUser, Role::Manager);

        $oldPassword = self::$adminUser->password;
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'password' => '12345678$$',
                'password_confirmation' => '12345678$$',
                'email' => self::$adminUser->email,
                'phone_number' => self::$adminUser->mobileDialingPhoneNumber,
                'phone_country_code' => self::$adminUser->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);

        $this->assertNotEquals($oldPassword, self::$adminUser->password);
    }

    /**
     * @return void
     */
    public function test_admin_update_his_profile_without_first_last_and_email(): void
    {
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile')
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name')
            ->assertJsonValidationErrorFor('email');
    }

    /**
     * @return void
     */
    public function test_admin_update_his_profile_without_first_name(): void
    {
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile', [
                'email' => 'test@bim.com',
                'last_name' => 'test name',
                'phone_number' => self::$adminUser->mobileDialingPhoneNumber,
                'phone_country_code' => self::$adminUser->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name');
    }

    /**
     * @return void
     */
    public function test_admin_update_his_profile_without_last_name(): void
    {
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile', [
                'first_name' => 'test name',
                'email' => 'test@bim.com',
                'phone_number' => self::$adminUser->mobileDialingPhoneNumber,
                'phone_country_code' => self::$adminUser->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('last_name');
    }

    /**
     * @return void
     */
    public function test_admin_update_his_profile_without_email(): void
    {
        $this->actingAs(self::$adminUser)
            ->putJson('api/v1/admin/auth/profile', [
                'first_name' => 'test name',
                'last_name' => 'test name',
                'phone_number' => self::$adminUser->mobileDialingPhoneNumber,
                'phone_country_code' => self::$adminUser->phoneNumberCountryCode,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    }
}
