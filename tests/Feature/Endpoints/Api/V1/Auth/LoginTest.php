<?php

namespace Tests\Feature\Endpoints\Api\V1\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_body(): void
    {
        $response = $this->postJson('api/v1/auth/login');

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The email field is required. (and 2 more errors)',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                    'password' => [
                        'The password field is required.',
                    ],
                    'source' => [
                        'The source field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_password_and_source(): void
    {
        $response = $this->postJson('api/v1/auth/login', ['email' => 'a@a.a']);
        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The password field is required. (and 1 more error)',
                'errors' => [
                    'password' => [
                        'The password field is required.',
                    ],
                    'source' => [
                        'The source field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_source_and_email(): void
    {
        $response = $this->postJson('api/v1/auth/login', ['password' => '12345678']);
        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The email field is required. (and 1 more error)',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                    'source' => [
                        'The source field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_email_and_password(): void
    {
        $response = $this->postJson('api/v1/auth/login', ['source' => 'admin']);
        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The email field is required. (and 1 more error)',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_email(): void
    {
        $response = $this->postJson('api/v1/auth/login', [
            'password' => '12345678',
            'source' => 'admin',
        ]);
        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The email field is required.',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_password(): void
    {
        $response = $this->postJson('api/v1/auth/login', [
            'email' => 'a@a.aa',
            'source' => 'admin',
        ]);
        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The password field is required.',
                'errors' => [
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_empty_source(): void
    {
        $response = $this->postJson('api/v1/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The source field is required.',
                'errors' => [
                    'source' => [
                        'The source field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_throw_exception_for_not_exist_user(): void
    {
        $response = $this->postJson('api/v1/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678',
            'source' => 'admin',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'These credentials do not match our records.',
            'errors' => [
                'email' => [
                    'These credentials do not match our records.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     *
     * @covers \App\Http\Controllers\Api\V1\Auth\LoginController::authenticate
     */
    public function test_login_success_for_exist_user(): void
    {
        $email = 'a@a.aa';
        $password = '12345678';
        $source = 'admin';

        User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);

        $response = $this->postJson('api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'source' => $source,
        ]);

        $response->assertStatus(200)->assertExactJson(
            [
                'data' => [
                    'token' => $response->getOriginalContent()['data']['token'],
                    'type' => $response->getOriginalContent()['data']['type'],
                    'company_id' => $response->getOriginalContent()['data']['company_id'],
                ],
            ]
        );
    }

    public function testTwoUsersWithSameEmailAndDifferentCompanyNotPassed()
    {
        $email = 'a@a.aa';
        $password = '12345678';
        $source = 'admin';

        $campanies = Company::factory(2)->create();

        $campanies->each(function ($company) use ($email, $password) {
            User::factory()->create([
                'email' => $email,
                'password' => $password,
                'company_id' => $company->id,
            ]);
        });

        $response = $this->postJson('api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'source' => $source,
        ]);

        $response->assertStatus(422)->assertExactJson([
            'message' => 'These credentials do not match our records.',
            'errors' => [
                'email' => [
                    'These credentials do not match our records.',
                ],
            ],
        ]);
    }

    public function testTwoUsersWithSameEmailAndDifferentCompanyPassedByUniqueName()
    {
        $email = 'a@a.aa';
        $password = '12345678';
        $source = 'admin';

        $campanies = Company::factory(2)->create();

        $campanies->each(function ($company) use ($email, $password) {
            User::factory()->create([
                'email' => $email,
                'password' => $password,
                'company_id' => $company->id,
            ]);
        });

        $response = $this->postJson('api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'source' => $source,
            'unique_name' => $campanies->first()->unique_name,
        ]);

        $response->assertStatus(200)->assertExactJson(
            [
                'data' => [
                    'token' => $response->getOriginalContent()['data']['token'],
                    'type' => $response->getOriginalContent()['data']['type'],
                    'company_id' => $response->getOriginalContent()['data']['company_id'],
                ],
            ]
        );
    }
}
