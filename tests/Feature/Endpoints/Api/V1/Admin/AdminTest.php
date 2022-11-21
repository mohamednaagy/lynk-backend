<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function test_list_admins(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->get('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'data',
        ]);
    }

    /**
     * @return void
     */
    public function test_store_admin_throw_exception_on_empty_first_name_and_last_name(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->postJson('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The first name field is required. (and 1 more error)',
            'errors' => [
                'first_name' => [
                    'The first name field is required.',
                ],
                'last_name' => [
                    'The last name field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_store_admin_throw_exception_on_empty_phone_country_code(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->postJson('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_number' => '012345678',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
            'errors' => [
                'phone_country_code' => [
                    'The phone country code field is required when phone number is present.',
                ],
                'phone_number' => [
                    'validation.phone',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_store_admin_throw_exception_on_empty_phone_number(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->postJson('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The phone number field is required.',
            'errors' => [
                'phone_number' => [
                    'The phone number field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_store_admin_throw_exception_on_empty_email(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->postJson('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The email field is required.',
            'errors' => [
                'email' => [
                    'The email field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_store_admin_throw_exception_on_empty_password_and_confirmation(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->postJson('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'email' => 'aa@aa.aa',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The password field is required. (and 1 more error)',
            'errors' => [
                'password' => [
                    'The password field is required.',
                ],
                'password_confirmation' => [
                    'The password confirmation field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_store_admin_success(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $response = $this->withToken($token)->postJson('/api/v1/admin/admins', [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(200)->assertExactJson([
            'data' => [],
        ]);
    }

    /**
     * @return void
     */
    public function test_update_admin_throw_exception_on_empty_first_name_and_last_name(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $authUserId = auth()->user()->getAuthIdentifier();

        $response = $this->withToken($token)->putJson('/api/v1/admin/admins/'.$authUserId, [
            'authorized_token' => $authorizationToken,
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The first name field is required. (and 1 more error)',
            'errors' => [
                'first_name' => [
                    'The first name field is required.',
                ],
                'last_name' => [
                    'The last name field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_update_admin_throw_exception_on_empty_phone_country_code(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $authUserId = auth()->user()->getAuthIdentifier();

        $response = $this->withToken($token)->putJson('/api/v1/admin/admins/'.$authUserId, [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_number' => '012345678',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
            'errors' => [
                'phone_country_code' => [
                    'The phone country code field is required when phone number is present.',
                ],
                'phone_number' => [
                    'validation.phone',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_update_admin_throw_exception_on_empty_phone_number(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $authUserId = auth()->user()->getAuthIdentifier();

        $response = $this->withToken($token)->putJson('/api/v1/admin/admins/'.$authUserId, [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'email' => 'aa@aa.aa',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The phone number field is required.',
            'errors' => [
                'phone_number' => [
                    'The phone number field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_update_admin_throw_exception_on_empty_email(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $authUserId = auth()->user()->getAuthIdentifier();

        $response = $this->withToken($token)->putJson('/api/v1/admin/admins/'.$authUserId, [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422)->assertExactJson([
            'message' => 'The email field is required.',
            'errors' => [
                'email' => [
                    'The email field is required.',
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_update_admin_success(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $authUserId = auth()->user()->getAuthIdentifier();

        $response = $this->withToken($token)->putJson('/api/v1/admin/admins/'.$authUserId, [
            'authorized_token' => $authorizationToken,
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'email' => 'aaa@aaa.aaa',
        ]);

        $response->assertStatus(200)->assertExactJson([
            'data' => [],
        ]);
    }

    /**
     * A basic feature test delete a customer.
     *
     * @return void
     */
    public function test_delete_admin(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $authUserId = auth()->user()->getAuthIdentifier();

        // get all customers data
        $response = $this->withToken($token)->deleteJson('api/v1/admin/admins/'.$authUserId, [
            'authorized_token' => $authorizationToken,
        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'data',
        ]);
    }

    /**
     * A basic feature test delete not found customer.
     *
     * @return void
     */
    public function test_delete_for_not_found_admin(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $wrongId = 1111;

        // get all customers data
        $response = $this->withToken($token)->deleteJson('api/v1/admin/admins/'.$wrongId, [
            'authorized_token' => $authorizationToken,
        ]);
        $response->assertStatus(400)->assertJsonStructure([
            'message',
        ]);
    }
}
