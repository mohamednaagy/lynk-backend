<?php

namespace Tests\Feature\Endpoints\Api\V1\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_is_successful(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Grantify::assignRoleToModel($user, Role::Customer);

        $response = $this->post('api/v1/auth/send-reset-password-link', [
            'email' => $user->email,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_is_successful_when_user_has_super_admin_role(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Grantify::assignRoleToModel($user, Role::Admin);

        $response = $this->post('api/v1/auth/send-reset-password-link', [
            'email' => $user->email,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_is_successful_when_user_has_lender_admin_role(): void
    {
        Notification::fake();

        [$company] = $this->createCompany();
        $user = $this->createLenderUser($company->id, Role::LenderAdmin);

        $response = $this->post('api/v1/auth/send-reset-password-link', [
            'email' => $user->email,
            'company_unique_name' => $company->unique_name,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email_on_invalid_company_unique_name(): void
    {
        Notification::fake();

        [$company] = $this->createCompany();
        $user = $this->createLenderUser($company->id, Role::LenderAdmin);

        $response = $this->post('api/v1/auth/send-reset-password-link', [
            'email' => $user->email,
            'company_unique_name' => 'unique_name',
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(302);
    }

    /**
     * Does not send a password reset email when the user does not exist.
     *
     * @return void
     */
    public function test_failure_of_sending_reset_password_email(): void
    {
        Notification::fake();

        $this->post('api/v1/auth/send-reset-password-link', [
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
        $user = User::factory()->create();
        Grantify::assignRoleToModel($user, Role::Customer);

        $token = Password::createToken($user);

        $response = $this->post('api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_when_user_has_super_admin_role(): void
    {
        $user = User::factory()->create();
        Grantify::assignRoleToModel($user, Role::Admin);

        $token = Password::createToken($user);

        $response = $this->post('api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_when_user_has_lender_admin_role(): void
    {
        [$company] = $this->createCompany();
        $user = $this->createLenderUser($company->id, Role::LenderAdmin);

        $token = Password::createToken($user);

        $response = $this->post('api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'company_unique_name' => $company->unique_name,
            'password' => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $this->assertTrue(Hash::check('Password@1234', $user->fresh()->password));
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password_on_invalid_company_unique_name(): void
    {
        [$company] = $this->createCompany();
        $user = $this->createLenderUser($company->id, Role::LenderAdmin);

        $token = Password::createToken($user);

        $response = $this->post('api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'company_unique_name' => 'unique_name',
            'password' => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $response->assertStatus(302);
    }
}
