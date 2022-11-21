<?php

namespace Tests\Feature\Auth;

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

        // logout
        $logoutResponse = $this->withToken($token)->postJson('api/v1/auth/logout');
        $logoutResponse->assertStatus(204);
    }
}
