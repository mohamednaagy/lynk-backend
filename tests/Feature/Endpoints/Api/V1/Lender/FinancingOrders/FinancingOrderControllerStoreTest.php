<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FinancingOrderControllerStoreTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static array $orderDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$orderDetails = [
            'national_id' => '1001280070',
            'amount' => '200',
            'selling_price' => '220',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'is_verification_required' => true,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_create_order(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_national_id_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['national_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The national ID field is required.',
                'errors' => [
                    'national_id' => [
                        '0' => 'The national ID field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_amount_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['amount']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The amount field is required. (and 1 more error)',
                'errors' => [
                    'amount' => [
                        0 => 'The amount field is required.',
                    ],
                    'selling_price' => [
                        0 => 'The selling price must be greater than or equal to amount.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_selling_price_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['selling_price']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
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
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['phone_country_code']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
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
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['phone_number']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
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
    public function test_that_auth_user_without_is_verification_required_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['is_verification_required']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The is verification required field is required.',
                'errors' => [
                    'is_verification_required' => [
                        'The is verification required field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
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
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_supervisor_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
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
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_billing_user_cant_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
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
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }
}
