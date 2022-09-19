<?php

namespace Tests\Feature;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Modules\Permission\Facades\Grantify;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test get customers list.
     *
     * @return void
     */
    public function test_get_customers_list(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # get all customers data
        $response = $this->withToken($token)->getJson('api/v1/admin/customers', [
            'authorized_token' => $authorizationToken
        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    /**
     * A basic feature test create a customer.
     *
     * @return void
     */
    public function test_create_customer(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # create a customer
        $response = $this->withToken($token)->postJson('api/v1/admin/customers', array_merge([
            'authorized_token' => $authorizationToken
        ], $this->getCustomerData()));
        $response->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    /**
     * A basic feature test create a customer throw exception for empty first last name.
     *
     * @return void
     */
    public function test_create_customer_throw_exception_for_empty_first_last_name(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # get all customers data
        $response = $this->withToken($token)->postJson('api/v1/admin/customers', array_merge([
            'authorized_token' => $authorizationToken
        ], Arr::except($this->getCustomerData(), ['first_name', 'last_name'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The first name field is required. (and 1 more error)",
                "errors" => [
                    "first_name" => [
                        "The first name field is required."
                    ],
                    "last_name" => [
                        "The last name field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * A basic feature test create a customer throw exception for empty email.
     *
     * @return void
     */
    public function test_create_customer_throw_exception_for_empty_email(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # get all customers data
        $response = $this->withToken($token)->postJson('api/v1/admin/customers', array_merge([
            'authorized_token' => $authorizationToken
        ], Arr::except($this->getCustomerData(), ['email'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The email field is required.",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test create a customer throw exception for empty phone number.
     *
     * @return void
     */
    public function test_create_customer_throw_exception_for_empty_phone_number(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # get all customers data
        $response = $this->withToken($token)->postJson('api/v1/admin/customers', array_merge([
            'authorized_token' => $authorizationToken
        ], Arr::except($this->getCustomerData(), ['phone_number'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The phone number field is required.",
                "errors" => [
                    "phone_number" => [
                        "The phone number field is required."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test create a customer throw exception for empty password.
     *
     * @return void
     */
    public function test_create_customer_throw_exception_for_empty_password(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # get all customers data
        $response = $this->withToken($token)->postJson('api/v1/admin/customers', array_merge([
            'authorized_token' => $authorizationToken
        ], Arr::except($this->getCustomerData(), ['password'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The password field is required.",
                "errors" => [
                    "password" => [
                        "The password field is required."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test create a customer throw exception for invalid role.
     *
     * @return void
     */
    public function test_create_customer_throw_exception_for_invalid_role(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $customerData = $this->getCustomerData();
        $customerData['role'] = 'Test Role';

        # get all customers data
        $response = $this->withToken($token)->postJson('api/v1/admin/customers', array_merge([
            'authorized_token' => $authorizationToken
        ], $customerData));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The selected role is invalid.",
                "errors" => [
                    "role" => [
                        "The selected role is invalid."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test update a customer.
     *
     * @return void
     */
    public function test_update_customer(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $customer = $this->createCustomer();
        $customerData = $this->getCustomerData();
        $customerData['first_name'] = 'Sarah';

        # create a customer
        $response = $this->withToken($token)->putJson('api/v1/admin/customers/'. $customer->id,
            array_merge([
                'authorized_token' => $authorizationToken
            ], $customerData));
        $response->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    /**
     * A basic feature test update a customer throw exception for empty first last name.
     *
     * @return void
     */
    public function test_update_customer_throw_exception_for_empty_first_last_name(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $customer = $this->createCustomer();

        # get all customers data
        $response = $this->withToken($token)->putJson('api/v1/admin/customers/'. $customer->id,
            array_merge([
                'authorized_token' => $authorizationToken
            ], Arr::except($this->getCustomerData(), ['first_name', 'last_name'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The first name field is required. (and 1 more error)",
                "errors" => [
                    "first_name" => [
                        "The first name field is required."
                    ],
                    "last_name" => [
                        "The last name field is required."
                    ]
                ]
            ]
        );
    }

    /**
     * A basic feature test update a customer throw exception for empty email.
     *
     * @return void
     */
    public function test_update_customer_throw_exception_for_empty_email(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $customer = $this->createCustomer();

        # get all customers data
        $response = $this->withToken($token)->putJson('api/v1/admin/customers/'. $customer->id,
            array_merge([
                'authorized_token' => $authorizationToken
            ], Arr::except($this->getCustomerData(), ['email'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The email field is required.",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test update a customer throw exception for empty phone number.
     *
     * @return void
     */
    public function test_update_customer_throw_exception_for_empty_phone_number(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();
        $customer = $this->createCustomer();

        # get all customers data
        $response = $this->withToken($token)->putJson('api/v1/admin/customers/'. $customer->id,
            array_merge([
                'authorized_token' => $authorizationToken
            ], Arr::except($this->getCustomerData(), ['phone_number'])));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The phone number field is required.",
                "errors" => [
                    "phone_number" => [
                        "The phone number field is required."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test update a customer throw exception for invalid role.
     *
     * @return void
     */
    public function test_update_customer_throw_exception_for_invalid_role(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        $customer = $this->createCustomer();
        $customerData = $this->getCustomerData();
        $customerData['role'] = 'Test Role';

        # get all customers data
        $response = $this->withToken($token)->putJson('api/v1/admin/customers/'. $customer->id,
            array_merge([
                'authorized_token' => $authorizationToken
            ], $customerData));
        $response->assertStatus(422)->assertExactJson(
            [
                "message" => "The selected role is invalid.",
                "errors" => [
                    "role" => [
                        "The selected role is invalid."
                    ],
                ]
            ]
        );
    }

    /**
     * A basic feature test show a customer.
     *
     * @return void
     */
    public function test_show_customer(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # create customer
        $customer = $this->createCustomer();

        # get all customers data
        $response = $this->withToken($token)->getJson('api/v1/admin/customers/'. $customer->id, [
            'authorized_token' => $authorizationToken
        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    /**
     * A basic feature test show not found customer.
     *
     * @return void
     */
    public function test_show_for_not_found_customer(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # create customer
        $customer = $this->createCustomer();

        $wrongId = 1111;

        # get all customers data
        $response = $this->withToken($token)->getJson('api/v1/admin/customers/'. $wrongId, [
            'authorized_token' => $authorizationToken
        ]);
        $response->assertStatus(400)->assertJsonStructure([
            'message'
        ]);
    }

    /**
     * A basic feature test delete a customer.
     *
     * @return void
     */
    public function test_delete_customer(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # create customer
        $customer = $this->createCustomer();

        # get all customers data
        $response = $this->withToken($token)->deleteJson('api/v1/admin/customers/'. $customer->id, [
            'authorized_token' => $authorizationToken
        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    /**
     * A basic feature test delete not found customer.
     *
     * @return void
     */
    public function test_delete_for_not_found_customer(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        # create customer
        $customer = $this->createCustomer();

        $wrongId = 1111;

        # get all customers data
        $response = $this->withToken($token)->deleteJson('api/v1/admin/customers/'. $wrongId, [
            'authorized_token' => $authorizationToken
        ]);
        $response->assertStatus(400)->assertJsonStructure([
            'message'
        ]);
    }

    private function createCustomer(): User
    {
        $customerData = $this->getCustomerData();
        $customerData['password'] = Hash::make($customerData['password']);
        $customerData['phone_number'] = phone($customerData['phone_number'], $customerData['phone_country_code']);

        $customer = User::create($customerData);
        Grantify::assignRoleToModel($customer, Role::Customer);

        return $customer;
    }

    private function getCustomerData(): array
    {
        return [
            'first_name' => 'Chelsea',
            'last_name' => 'Castro',
            'phone_country_code' => 'BE',
            'phone_number' => '012345678',
            'email' => 'chelsea@castro.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'role' => Role::Customer,
        ];
    }
}
