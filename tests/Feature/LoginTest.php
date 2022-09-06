<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_body(): void
    {
        $response = $this->postJson('api/auth/login');
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The email field is required. (and 2 more errors)",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ],
                    "password" => [
                        "The password field is required."
                    ],
                    "source" => [
                        "The source field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_password_and_source(): void
    {
        $response = $this->postJson('api/auth/login', ['email' => 'a@a.a']);
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The password field is required. (and 1 more error)",
                "errors" => [
                    "password" => [
                        "The password field is required."
                    ],
                    "source" => [
                        "The source field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_source_and_email(): void
    {
        $response = $this->postJson('api/auth/login', ['password' => '12345678']);
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The email field is required. (and 1 more error)",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ],
                    "source" => [
                        "The source field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_email_and_password(): void
    {
        $response = $this->postJson('api/auth/login', ['source' => 'admin']);
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The email field is required. (and 1 more error)",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ],
                    "password" => [
                        "The password field is required."
                    ],
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_email(): void
    {
        $response = $this->postJson('api/auth/login', [
            'password' => '12345678',
            'source' => 'admin'
        ]);
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The email field is required.",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_password(): void
    {
        $response = $this->postJson('api/auth/login', [
            'email' => 'a@a.aa',
            'source' => 'admin'
        ]);
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The password field is required.",
                "errors" => [
                    "password" => [
                        "The password field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_source(): void
    {
        $response = $this->postJson('api/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678'
        ]);
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The source field is required.",
                "errors" => [
                    "source" => [
                        "The source field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_not_exist_user(): void
    {
        $response = $this->postJson('api/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678',
            'source' => 'admin'
        ]);
        $response->assertStatus(422)->assertExactJson([
            "message" => "These credentials do not match our records.",
            "errors" => [
                "email" => [
                    "These credentials do not match our records."
                ]
            ]
        ]);
    }

    /**
     * @return void
     * @covers \App\Http\Controllers\Api\Auth\LoginController::authenticate
     */
    public function test_login_success_exception_for_exist_user(): void
    {
        $email = 'a@a.aa';
        $passwordPlainText = '12345678';
        $passwordEncrypted = bcrypt('12345678');
        $source = 'admin';

        User::factory()->create([
            'email' => $email,
            'password' => $passwordEncrypted
        ]);

        $response = $this->postJson('api/auth/login', [
            'email' => $email,
            'password' => $passwordPlainText,
            'source' => $source
        ]);

        $response->assertStatus(200)->assertExactJson([
            "token" => $response->getOriginalContent()['token']
        ]);
    }
}
