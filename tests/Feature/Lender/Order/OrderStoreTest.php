<?php

namespace Tests\Feature\Lender\Order;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Traits\Test\OrderTrait;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OrderStoreTest extends TestCase
{
    use RefreshDatabase, OrderTrait;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompanyDetails();
        self::$userLender = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderAdmin);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_create_order(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_national_id_cant_create_order(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_amount_cant_create_order(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The amount field is required.',
                'errors' => [
                    'amount' => [
                        0 => 'The amount field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_selling_price_cant_create_order(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The selling price field is required.',
                'errors' => [
                    'selling_price' => [
                        0 => 'The selling price field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_phone_country_code_cant_create_order(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not a valid phone number.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_phone_number_cant_create_order(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone number field is required. (and 1 more error)',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                    'national_id' => [
                        "Phone number does't belong to national ID/Iqama",
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'phone_country_code',
                    'phone_number',
                    'phone_number_formatted',
                    'id',
                    'status' => [
                        'description',
                        'value',
                    ],
                    'company_id',
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'contract',
                    'power_of_attorney',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }
}
