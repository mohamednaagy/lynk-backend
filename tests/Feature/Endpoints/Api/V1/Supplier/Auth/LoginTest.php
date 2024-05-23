<?php

namespace Endpoints\Api\V1\Supplier\Auth;

use App\Enums\CommoitySupplierStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithSupplier;

class LoginTest extends TestCase
{
    use InteractsWithSupplier , RefreshDatabase;

    /**
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function setUp(): void
    {
        parent::setUp();
        $supplier = $this->createSupplier(['unique_name' => 'new_supplier']);
    }

    public function test_login_throw_exception_for_empty_body(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login');

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The unique name field is required. (and 3 more errors)',
                'errors' => [
                    'unique_name' => [
                        'The unique name field is required.',
                    ],
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_empty_password_and_source(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', ['email' => 'a@a.a', 'unique_name' => 'new_supplier']);
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_empty_source_and_email(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', ['password' => '12345678', 'unique_name' => 'new_supplier']);
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_empty_email_and_password(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', ['source' => 'supplier', 'unique_name' => 'new_supplier']);
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_empty_email(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', [
            'password' => '12345678',
            'source' => 'supplier',
            'unique_name' => 'new_supplier',
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_empty_password(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => 'a@a.aa',
            'source' => 'supplier',
            'unique_name' => 'new_supplier',
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_empty_source(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678',
            'unique_name' => 'new_supplier',
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_throw_exception_for_invalid_unique_name(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678',
            'source' => 'supplier',
            'unique_name' => 'test',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The selected unique name is invalid.',
            'errors' => [
                'unique_name' => [
                    'The selected unique name is invalid.',
                ],
            ],
        ]);
    }

    public function test_login_throw_exception_for_not_exist_user(): void
    {
        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => 'a@a.aa',
            'password' => '12345678',
            'source' => 'supplier',
            'unique_name' => 'new_supplier',
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
     * @covers \App\Http\Controllers\Api\V1\Supplier\Auth::authenticate
     */
    public function test_login_success_for_exist_user(): void
    {
        $email = 'a@a.aa';
        $password = '12345678';
        $source = 'admin';

        $supplier = $this->createSupplier();
        User::factory()->create([
            'email' => $email,
            'password' => $password,
            'company_id' => $supplier->id,
        ]);

        User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);

        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => $email,
            'password' => $password,
            'source' => $source,
            'unique_name' => $supplier->unique_name,

        ]);

        $response->assertStatus(200)->assertJsonStructure(
            [
                'data' => [
                    'vid',
                ],
            ]
        );
    }

    public function testTwoUsersWithSameEmailAndDifferentCompanyPassedByUniqueName()
    {
        $email = 'a@a.aa';
        $password = '12345678';
        $source = 'admin';

        $supplier = $this->createSupplier();
        $supplier2 = $this->createSupplier();

        User::factory()->create([
            'email' => $email,
            'password' => $password,
            'company_id' => $supplier->id,
        ]);

        User::factory()->create([
            'email' => $email,
            'password' => $password,
            'company_id' => $supplier2->id,
        ]);

        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => $email,
            'password' => $password,
            'source' => $source,
            'unique_name' => $supplier->unique_name,
        ]);

        $response->assertStatus(200)->assertJsonStructure(
            [
                'data' => [
                    'vid',
                ],
            ]
        );
    }

    public function test_login_user_with_inactive_supplier()
    {
        $email = 'a@a.aa';
        $password = '12345678';
        $source = 'supplier';
        $supplier = $this->createSupplier([], CommoitySupplierStatus::Inactive);
        User::factory()->create([
            'email' => $email,
            'password' => $password,
            'company_id' => $supplier->id,
        ]);
        $response = $this->postJson('api/v1/supplier/auth/login', [
            'email' => $email,
            'password' => $password,
            'source' => $source,
            'unique_name' => $supplier->unique_name,
        ]);

        $response->assertStatus(422)->assertExactJson([
            'message' => 'The selected unique name is invalid.',
            'errors' => ['unique_name' => [
                'The selected unique name is invalid.',
            ],
            ],
        ]);

        $email2 = 'a2@a.aa';
        $password2 = '12345678';
        $source2 = 'supplier';
        $supplier2 = $this->createSupplier([], CommoitySupplierStatus::Active);
        User::factory()->create([
            'email' => $email2,
            'password' => $password2,
            'company_id' => $supplier2->id,
        ]);
        $response2 = $this->postJson('api/v1/supplier/auth/login', [
            'email' => $email2,
            'password' => $password2,
            'source' => $source2,
            'unique_name' => $supplier2->unique_name,
        ]);

        $response2->assertStatus(200)->assertJsonStructure(
            [
                'data' => [
                    'vid',
                ],
            ]
        );
    }
}
