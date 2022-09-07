<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::logout
     */
    public function test_logout_success_for_exist_user(): void
    {
        $email = 'a@a.aa';
        $passwordPlainText = '12345678';
        $passwordEncrypted = bcrypt('12345678');
        $source = 'admin';

        # create user
        User::factory()->create([
            'email' => $email,
            'password' => $passwordEncrypted
        ]);

        # login user
        $loginResponse = $this->postJson('api/auth/login', [
            'email' => $email,
            'password' => $passwordPlainText,
            'source' => $source
        ]);
        $token = $loginResponse->getOriginalContent()['token'];
        $loginResponse->assertStatus(200)->assertExactJson([
            "token" => $token
        ]);

        # get auth user data
        $getAuthUserResponse = $this->withToken($token)->getJson('api/auth');
        $getAuthUserResponse->assertStatus(200)->assertJsonStructure([
            "data"
        ]);

        # logout
        $logoutResponse = $this->withToken($token)->postJson('api/auth/logout');
        $logoutResponse->assertStatus(204);

        # used ref: https://github.com/laravel/sanctum/issues/256
        $this->refreshApplication();

        # get auth user data after logout
        $getAuthUserResponseAfterLogout = $this->withToken($token)->getJson('api/auth');
        $getAuthUserResponseAfterLogout->assertStatus(401)->assertExactJson([
            "message" => "Unauthenticated."
        ]);
    }
}
