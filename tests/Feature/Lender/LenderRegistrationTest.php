<?php

namespace Tests\Feature\Lender;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LenderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_valid_all_inputs_register()
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'Joe',
            'last_name' => 'Doe',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(201)->assertJsonStructure(
            [
                'data' => [
                    'token',
                    'type',
                    'company_id',
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_first_name_and_last_name()
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => '',
            'last_name' => '',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The first name field is required. (and 1 more error)',
                'errors' => [
                    'first_name' => [
                        'The first name field is required.',
                    ],
                    'last_name' => [
                        'The last name field is required.',
                    ],

                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_phone_country_code()
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => '',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not valid phone number.',
                    ],

                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_phone_number()
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The phone number field is required.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_email()
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => '',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
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

    public function test_register_lender_throw_exception_on_invalid_email()
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The email must be a valid email address.',
                'errors' => [
                    'email' => [
                        'The email must be a valid email address.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_password(): void
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => '',
            'password_confirmation' => 'qwer',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
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

    public function test_register_lender_throw_exception_on_empty_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'qwer',
            'password_confirmation' => '',
            'source' => 'Postman',
            'company_name' => 'test company1',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The password confirmation does not match. (and 4 more errors)',
                'errors' => [
                    'password' => [
                        'The password confirmation does not match.',
                        'The password must be at least 8 characters.',
                        'The password must contain at least one uppercase and one lowercase letter.',
                        'The password must contain at least one symbol.',
                        'The password must contain at least one number.',

                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_company(): void
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => '',
            'company_unique_name' => 'lynk06',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company name field is required.',
                'errors' => [
                    'company_name' => [
                        'The company name field is required.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_company_unique_name(): void
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test',
            'company_unique_name' => '',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company unique name field is required.',
                'errors' => [
                    'company_unique_name' => [
                        'The company unique name field is required.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_exist_company_unique_name(): void
    {
        $companyTest = $this->createCompany();

        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test',
            'company_unique_name' => 'CompanyTest1',
            'company_cr' => '123456789101',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company unique name has already been taken.',
                'errors' => [
                    'company_unique_name' => [
                        'The company unique name has already been taken.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_company_cr(): void
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test',
            'company_unique_name' => 'CompanyTest0',
            'company_cr' => '',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company cr field is required.',
                'errors' => [
                    'company_cr' => [
                        'The company cr field is required.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_exist_company_cr(): void
    {
        $companyTest = $this->createCompany();

        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'test',
            'company_unique_name' => 'CompanyTest2',
            'company_cr' => '123',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company cr has already been taken.',
                'errors' => [
                    'company_cr' => [
                        'The company cr has already been taken.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_empty_source(): void
    {
        $response = $this->postJson('/api/v1/lender/register', [
            'first_name' => 'youssof',
            'last_name' => 'okiel',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'y.okiel@g.c',
            'password' => 'Qwer@1234',
            'password_confirmation' => 'Qwer@1234',
            'source' => '',
            'company_name' => 'test',
            'company_unique_name' => 'CompanyTest0',
            'company_cr' => '123456789101',
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

    public function createCompany(): Company
    {
        return Company::factory()->create([
            'name' => 'Company Test',
            'unique_name' => 'CompanyTest1',
            'company_cr' => '123',
            'status' => 3,
            'order_cost' => 150,
            'does_order_require_approval' => 0,
        ]);
    }
}
