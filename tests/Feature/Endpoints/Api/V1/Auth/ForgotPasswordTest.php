<?php

namespace Tests\Feature\Endpoints\Api\V1\Auth;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static User $generalUser;

    private static string $sendResetPasswordUrl;

    private static string $resetPasswordUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        self::$generalUser = User::factory()->create();
        [self::$company] = $this->createCompany();
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$sendResetPasswordUrl = 'api/v1/auth/send-reset-password-link';
        self::$resetPasswordUrl = 'api/v1/auth/reset-password';
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_is_successful(): void
    {
        Notification::fake();

        Grantify::assignRoleToModel(self::$generalUser, Role::Customer);

        $response = $this->postJson(self::$sendResetPasswordUrl, [
            'email' => self::$generalUser->email,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(self::$generalUser, ResetPassword::class);
    }

    /**
     * Sends the password reset email when the user has super admin role.
     *
     * @return void
     */
    public function test_sending_reset_password_email_is_successful_when_user_has_super_admin_role(): void
    {
        Notification::fake();

        Grantify::assignRoleToModel(self::$generalUser, Role::Admin);

        $response = $this->postJson(self::$sendResetPasswordUrl, [
            'email' => self::$generalUser->email,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(self::$generalUser, ResetPassword::class);
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_is_successful_when_user_has_lender_admin_role(): void
    {
        Notification::fake();

        $response = $this->postJson(self::$sendResetPasswordUrl, [
            'email' => self::$userLender->email,
            'company_unique_name' => self::$company->unique_name,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(self::$userLender, ResetPassword::class);
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_on_invalid_company_unique_name(): void
    {
        Notification::fake();

        $response = $this->postJson(self::$sendResetPasswordUrl, [
            'email' => self::$userLender->email,
            'company_unique_name' => 'unique_name',
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'The selected company identifier is invalid.',
            'errors' => [
                'company_unique_name' => [
                    'The selected company identifier is invalid.',
                ],
            ],
        ]);
    }

    /**
     * Does not send a password reset email when the user does not exist.
     *
     * @return void
     */
    public function test_failure_of_sending_reset_password_email(): void
    {
        Notification::fake();

        $this->postJson(self::$sendResetPasswordUrl, [
            'email' => 'invalid@email.com',
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        Notification::assertNothingSent();
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password(): void
    {
        $token = Password::createToken(self::$generalUser);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => self::$generalUser->email,
            'password' => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $this->assertTrue(Hash::check('Password@1234', self::$generalUser->fresh()->password));
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_when_user_has_super_admin_role(): void
    {
        Grantify::assignRoleToModel(self::$generalUser, Role::Admin);

        $token = Password::createToken(self::$generalUser);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => self::$generalUser->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(Hash::check('password', self::$generalUser->fresh()->password));
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_when_user_has_lender_admin_role(): void
    {
        $token = Password::createToken(self::$userLender);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => self::$userLender->email,
            'company_unique_name' => self::$company->unique_name,
            'password' => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $this->assertTrue(Hash::check('Password@1234', self::$userLender->fresh()->password));
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_on_empty_email(): void
    {
        $token = Password::createToken(self::$userLender);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => '',
            'company_unique_name' => self::$company->unique_name,
            'password' => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'The email field is required.',
            'errors' => [
                'email' => [
                    'The email field is required.',
                ],
            ],
        ]);
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_on_invalid_company_unique_name(): void
    {
        $token = Password::createToken(self::$userLender);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => self::$userLender->email,
            'company_unique_name' => 'unique_name',
            'password' => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'The selected company identifier is invalid.',
            'errors' => [
                'company_unique_name' => [
                    'The selected company identifier is invalid.',
                ],
            ],
        ]);
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_on_invalid_password(): void
    {
        $token = Password::createToken(self::$userLender);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => self::$userLender->email,
            'company_unique_name' => self::$company->unique_name,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'The password must contain at least one uppercase and one lowercase letter. (and 2 more errors)',
            'errors' => [
                'password' => [
                    'The password must contain at least one uppercase and one lowercase letter.',
                    'The password must contain at least one symbol.',
                    'The password must contain at least one number.',
                ],
            ],
        ]);
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_on_empty_password_and_password_confirmation(): void
    {
        $token = Password::createToken(self::$userLender);

        $response = $this->postJson(self::$resetPasswordUrl, [
            'token' => $token,
            'email' => self::$userLender->email,
            'company_unique_name' => self::$company->unique_name,
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'The password field is required.',
            'errors' => [
                'password' => [
                    'The password field is required.',
                ],
            ],
        ]);
    }
}
