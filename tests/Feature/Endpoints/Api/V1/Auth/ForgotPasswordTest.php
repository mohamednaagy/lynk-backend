<?php

namespace Tests\Feature\Endpoints\Api\V1\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function test_sending_reset_password_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('api/v1/auth/send-reset-password-link', [
            'email' => $user->email,
            'redirect_url' => 'http://localhost:8000/api/v1/reset-password',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Does not send a password reset email when the user does not exist.
     *
     * @return void
     */
    public function test_failure_of_sending_reset_password_email(): void
    {
        $this->doesntExpectJobs(ResetPassword::class);

        $this->post('api/v1/auth/send-reset-password-link', ['email' => 'invalid@email.com']);
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function test_resetting_user_password(): void
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
