<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class MakeOrderProceedTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    const BaseUrl = 'api/v1/lender/orders/';

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static string $orderProceedUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::PendingApproval,
            ]
        );
        self::$orderProceedUrl = self::BaseUrl.self::$financingOrder->getOriginal('id').'/proceed';

        // create trader order
        self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_make_order_proceed(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_contract_signed_for_auth_user_has_lender_supervisor_role(): void
    {
        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_contract_signed_for_auth_user_has_lender_api_user_role(): void
    {
        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_lender_billing_cannot_make_order_proceed_on_contract_signed(): void
    {
        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right roles.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_lender_creator_cannot_make_order_proceed_on_contract_signed(): void
    {
        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right roles.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_user_with_not_verified_email_cannot_make_order_proceed_on_contract_signed(): void
    {
        // update user email verified at to be null
        self::$userLender->email_verified_at = null;
        self::$userLender->save();

        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('error.must_verify_email'))
                    ->where('code', 1008)
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_user_when_company_not_active_cannot_make_order_proceed_on_contract_signed(): void
    {
        // update user email verified at to be null
        self::$company->status = CompanyStatus::Pending;
        self::$company->save();

        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('error.company_not_active'))
                    ->where('code', 1015)
            );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_empty_case(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => '',
            ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The case field is required.',
                'errors' => [
                    'case' => [
                        'The case field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_invalid_case(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => 'TEST_PROCEED_CASE',
            ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The value you have entered is invalid.',
                'errors' => [
                    'case' => [
                        'The value you have entered is invalid.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_order_status_doesnt_follow_sequence(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(400)->assertExactJson([
            'message' => __('error.order_status_doesnt_follow_sequence'),
            'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
        ]);
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_contract_signed(): void
    {
        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)->assertJsonStructure([
            'data',
        ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->getOriginal('id'))->status->value,
            FinancingOrderStatus::ContractSigned
        );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_cannot_work_if_is_verification_required_set_as_true(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ]);

        $response->assertStatus(400)->assertExactJson([
            'message' => __('error.order_status_doesnt_follow_sequence'),
            'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
        ]);
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_client_wakala_accepted(): void
    {
        // update financing order is_verification_required to be able to move to client wakala accepted
        self::$financingOrder->is_verification_required = false;
        self::$financingOrder->status = FinancingOrderStatus::WaitingClientWakala;
        self::$financingOrder->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ]);

        $response->assertStatus(200)->assertJsonStructure([
            'data',
        ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->getOriginal('id'))->status->value,
            FinancingOrderStatus::WaitingPurchasingCommodity
        );
    }
}
