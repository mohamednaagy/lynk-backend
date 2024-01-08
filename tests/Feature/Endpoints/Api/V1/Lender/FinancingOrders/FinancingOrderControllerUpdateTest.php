<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FinancingOrderControllerUpdateTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static Builder|Model $order;

    private static Builder|Model $orderOwnedByOrderCreator;

    private static array $updatedOrderDetails;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$order = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::Rejected]);
        self::$orderOwnedByOrderCreator = $this->createOrder(self::$company->id, self::$userLenderOrderCreator->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$updatedOrderDetails = [
            'national_id' => '2553451234',
            'amount' => '300',
            'selling_price' => '320',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'customer_name' => '500112233',
            'is_verification_required' => 1,
        ];
    }

    public function test_that_un_auth_user_cant_update_order(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_update_order_with_force_unique_reference_number(): void
    {
        self::$company->update(['force_unique_reference_number' => true]);

        $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::InProgress, 'reference_number' => '123']);

        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$userLenderAdmin)
            ->putJson('api/v1/lender/orders/'.self::$order->id, array_merge(self::$updatedOrderDetails, ['reference_number' => '123']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('reference_number');
    }

    public function test_that_auth_user_without_national_id_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['national_id']))
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

    public function test_that_auth_user_without_amount_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['amount']))
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

    public function test_that_auth_user_without_selling_price_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['selling_price']))
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

    public function test_that_auth_user_without_phone_country_code_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['phone_country_code']))
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

    public function test_that_auth_user_without_phone_number_and_is_verification_required_true_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['phone_number']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone number field is required when is verification required is 1.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required when is verification required is 1.',
                    ],
                ],
            ]);
    }

    public function test_that_auth_user_without_phone_number_and_is_verification_required_false_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id,
                array_merge(Arr::except(self::$updatedOrderDetails, ['phone_number']), [
                    'is_verification_required' => 0,
                ])
            )
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_that_auth_user_without_customer_name_cant_update_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, Arr::except(self::$updatedOrderDetails, ['customer_name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The customer name field is required.',
                'errors' => [
                    'customer_name' => [
                        'The customer name field is required.',
                    ],
                ],
            ]);
    }

    public function test_that_admin_user_can_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$order->refresh(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_that_supervisor_user_can_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$order->refresh(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_that_billing_user_cant_update_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_order_creator_user_cant_update_not_owned_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_order_creator_user_can_update_owned_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$orderOwnedByOrderCreator->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$orderOwnedByOrderCreator->refresh(), new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_rejected_order_status_will_be_pending_approval_when_required_otherwise_pending_trader_order(): void
    {
        // Require approval case
        self::$company->update(['does_order_require_approval' => true]);
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK);
        $this->assertTrue(self::$order->refresh()->status->is(FinancingOrderStatus::PendingApproval));

        // Approval not Require  case
        self::$company->update(['does_order_require_approval' => false]);
        self::$order->update(['status' => FinancingOrderStatus::Rejected]);
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('api/v1/lender/orders/'.self::$order->id, self::$updatedOrderDetails)
            ->assertStatus(Response::HTTP_OK);
        $this->assertTrue(self::$order->refresh()->status->is(FinancingOrderStatus::PendingTraderOrder));
    }
}
