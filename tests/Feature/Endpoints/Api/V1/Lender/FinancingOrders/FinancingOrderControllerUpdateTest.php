<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class FinancingOrderControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static Builder|Model $order;

    private static array $updatedOrderDetails;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderSupervisor, 'lenderSupervisor@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderBilling, 'lenderBilling@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderOrderCreator, 'lenderOrderCreator@bim.com');
        self::$order = $this->createOrder(self::$company->getOriginal('id'), self::$userLenderAdmin->getOriginal('id'));
        self::$updatedOrderDetails = [
            'national_id' => '2553451234',
            'amount' => '300',
            'selling_price' => '320',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_order(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_national_id_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), Arr::except(self::$updatedOrderDetails, ['national_id']))
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
    public function test_that_auth_user_without_amount_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), Arr::except(self::$updatedOrderDetails, ['amount']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
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
    public function test_that_auth_user_without_selling_price_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), Arr::except(self::$updatedOrderDetails, ['selling_price']))
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
    public function test_that_auth_user_without_phone_country_code_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), Arr::except(self::$updatedOrderDetails, ['phone_country_code']))
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
    public function test_that_auth_user_without_phone_number_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), Arr::except(self::$updatedOrderDetails, ['phone_number']))
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
    public function test_that_admin_user_can_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => self::$order->refresh()->phoneNumberCountryCode,
                    'phone_number' => self::$order->refresh()->mobileDialingPhoneNumber,
                    'phone_number_formatted' => self::$order->refresh()->phone_number->formatInternational(),
                    'id' => self::$order->getOriginal('id'),
                    'status' => [
                        'description' => self::$order->status->description,
                        'value' => self::$order->status->value,
                    ],
                    'company_id' => self::$company->getOriginal('id'),
                    'reference_number' => self::$order->reference_number,
                    'national_id' => (string) self::$order->refresh()->national_id,
                    'amount' => (string) self::$order->amount,
                    'selling_price' => (string) self::$order->refresh()->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$order->approved_at !== null,
                    'status_reason' => self::$order->status_reason,
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_supervisor_user_can_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => self::$order->refresh()->phoneNumberCountryCode,
                    'phone_number' => self::$order->refresh()->mobileDialingPhoneNumber,
                    'phone_number_formatted' => self::$order->refresh()->phone_number->formatInternational(),
                    'id' => self::$order->getOriginal('id'),
                    'status' => [
                        'description' => self::$order->status->description,
                        'value' => self::$order->status->value,
                    ],
                    'company_id' => self::$company->getOriginal('id'),
                    'reference_number' => self::$order->reference_number,
                    'national_id' => (string) self::$order->refresh()->national_id,
                    'amount' => (string) self::$order->amount,
                    'selling_price' => (string) self::$order->refresh()->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$order->approved_at !== null,
                    'status_reason' => self::$order->status_reason,
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_billing_user_cant_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_cant_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
