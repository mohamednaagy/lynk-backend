<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender;

use App\Enums\Area;
use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;
use Tests\Traits\InteractsWithSettings;

class LenderRegistrationTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender, InteractsWithSettings;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register_on_all_valid_inputs(): void
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
            'company_cr' => '1234567891',
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

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register_and_check_if_lender_has_wallet(): void
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
            'company_cr' => '1234567891',
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

        $this->assertTrue(
            Company::find($response->json('data.company_id'))
                ->hasWallet(WalletType::CompanyWallet)
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register_and_check_if_lender_order_cost_as_in_default_settings(): void
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
            'company_cr' => '1234567891',
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

        $this->assertEquals(
            $this->getSettingsClass(Area::Lender)->default_order_cost,
            Company::find($response->json('data.company_id'))->order_cost->formatByDecimal()
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register_and_check_if_lender_status_as_in_default_settings(): void
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
            'company_cr' => '1234567891',
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

        $this->assertEquals(
            $this->getSettingsClass(Area::Lender)->default_company_registration_status,
            Company::find($response->json('data.company_id'))->status->value
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register_and_check_if_lender_does_order_require_approval_as_in_default_settings(): void
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
            'company_cr' => '1234567891',
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

        $this->assertEquals(
            $this->getSettingsClass(Area::Lender)->default_does_order_require_approval,
            Company::find($response->json('data.company_id'))->does_order_require_approval
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_register_and_check_if_lender_user_has_been_created_with_role_lender_admin(): void
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
            'company_cr' => '1234567891',
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

        $this->assertTrue(
            User::where('company_id', $response->json('data.company_id'))
                ->first()
                ->hasRole(Role::LenderAdmin)
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not a valid phone number.',
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
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
            'company_cr' => '1234567891',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company identifier field is required.',
                'errors' => [
                    'company_unique_name' => [
                        'The company identifier field is required.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_exist_company_unique_name(): void
    {
        $this->createCompany(data: [
            'name' => 'companyName',
            'unique_name' => 'lynk05',
            'company_cr' => '1234567891',
        ]);

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
            'company_unique_name' => 'lynk05',
            'company_cr' => '1234567892',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company identifier has already been taken.',
                'errors' => [
                    'company_unique_name' => [
                        'The company identifier has already been taken.',
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
                'message' => 'The company CR field is required.',
                'errors' => [
                    'company_cr' => [
                        'The company CR field is required.',
                    ],
                ],
            ]
        );
    }

    public function test_register_lender_throw_exception_on_exist_company_cr(): void
    {
        $this->createCompany(data: [
            'name' => 'companyName',
            'unique_name' => 'lynk05',
            'company_cr' => '1234567891',
        ]);

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
            'company_cr' => '1234567891',
        ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The company CR has already been taken.',
                'errors' => [
                    'company_cr' => [
                        'The company CR has already been taken.',
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
            'company_cr' => '1234567891',
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
}
