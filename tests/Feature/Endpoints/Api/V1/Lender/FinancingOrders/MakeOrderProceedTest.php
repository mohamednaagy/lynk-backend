<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class MakeOrderProceedTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    const BaseUrl = 'api/v1/lender/orders/';

    private static Company $company;

    private static User $userLender;

    private static Model|FinancingOrder $financingOrder;

    private static Model|TraderOrder $traderOrder;

    private static string $orderProceedUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$financingOrder = $this->createOrder(
            self::$company->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::PendingApproval,
            ]
        );
        self::$orderProceedUrl = self::BaseUrl.self::$financingOrder->getOriginal('id').'/proceed';

        self::$traderOrder = self::$financingOrder->traderOrders()
            ->create([
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

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        ]);

        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)
            ->assertJson(
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

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        ]);

        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)
            ->assertJson(
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

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_lender_order_creator_can_make_order_proceed_on_contract_signed(): void
    {
        // update financing order status to commodity purchased to be able to move to contract signed
        self::$financingOrder->status = FinancingOrderStatus::CommodityPurchased;
        self::$financingOrder->save();

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        ]);

        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ])
            ->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('data')->where('data', [])
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

        $this->actingAs(self::$userLender)
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

        $this->actingAs(self::$userLender)
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

        $response->assertStatus(422)
            ->assertExactJson(
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

        $response->assertStatus(422)
            ->assertExactJson(
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
        self::$traderOrder->traderHistories()->delete();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(400)
            ->assertExactJson([
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

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        ]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->getOriginal('id'))->status->value,
            FinancingOrderStatus::ContractSigned
        );
    }

    public function test_make_order_cannot_reprocessed_on_contract_signed(): void
    {
        self::$financingOrder->update([
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_for_client_wakala_if_order_doesnt_follow_sequence(): void
    {
        self::$traderOrder->traderHistories()->delete();
        self::$financingOrder->update(['is_verification_required' => false]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.pdf'),
            ]);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }

    /**
     * @return void
     */
    public function test_proceed_order_client_wakala_file_required_when_client_wakala_accepted_and_order_verification_is_false(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => false,
        ]);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('client_wakala');
    }

    /**
     * @return void
     */
    public function test_admin_proceed_order_client_wakala_should_be_pdf_file(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => false,
        ]);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.gif'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('client_wakala');
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_client_wakala_accepted_when_verification_is_not_required(): void
    {
        // update financing order is_verification_required to be able to move to client wakala accepted
        self::$financingOrder->is_verification_required = false;
        self::$financingOrder->status = FinancingOrderStatus::WaitingClientWakala;
        self::$financingOrder->save();

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.pdf'),
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(
            FinancingOrder::find(self::$financingOrder->getOriginal('id'))->status->value,
            FinancingOrderStatus::ClientWakalaCompleted
        );

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    /**
     * @return void
     */
    public function test_make_order_cannot_reprocessed_on_client_wakala_accepted(): void
    {
        self::$financingOrder->update([
            'is_verification_required' => false,
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ClientWakalaAccepted,
        ]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ClientWakalaAccepted,
                'client_wakala' => UploadedFile::fake()->create('client_wakala.pdf'),
            ]);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => __('error.order_status_doesnt_follow_sequence'),
                'code' => ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE,
            ]);
    }
}
