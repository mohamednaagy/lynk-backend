<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::logout
     */
    public function test_logout_success_for_exist_user(): void
    {
        $token = $this->login(test: 'logout');

        // get auth user data
        $getAuthUserResponse = $this->withToken($token)->getJson('api/auth');
        $getAuthUserResponse->assertStatus(200)->assertJsonStructure([
            'data',
        ]);

        // logout
        $logoutResponse = $this->withToken($token)->postJson('api/auth/logout');
        $logoutResponse->assertStatus(204);

        // used ref: https://github.com/laravel/sanctum/issues/256
        $this->refreshApplication();

        // get auth user data after logout
        $getAuthUserResponseAfterLogout = $this->withToken($token)->getJson('api/auth');
        $getAuthUserResponseAfterLogout->assertStatus(401)->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
