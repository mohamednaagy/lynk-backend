<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Tests\Support\FinancingOrders\CommittedOrder;
use Tests\Support\FinancingOrders\InProgressOrder;
use Tests\Support\FinancingOrders\OrderScenario;
use Tests\Support\FinancingOrders\TraderOrderScenario;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class MakeOrderProceedTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    const BaseUrl = 'api/v1/lender/orders/';

    private static Company $company;

    private static User $userLender;

    private static CommittedOrder $financingOrder;

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

        self::$financingOrder = OrderScenario::inProgress()
            ->lender(self::$company)
            ->creator(self::$userLender)
            ->commit();

        self::$traderOrder = InProgressOrder::of(self::$financingOrder)->createTraderOrder();

        self::$orderProceedUrl = self::BaseUrl.self::$financingOrder->id.'/proceed';
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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

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
        self::$userLender->update(['email_verified_at' => null]);

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

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

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::PurchasingCommodity);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson(self::$orderProceedUrl, [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::ClientWakala));
    }

    public function test_make_order_cannot_reprocessed_on_contract_signed(): void
    {
        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::ContractSigned);

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
        TraderOrderScenario::of(self::$traderOrder)
            ->reset();

        self::$financingOrder->requireVerification(false)->commit();

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
        self::$financingOrder->requireVerification(false)->commit();

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
        self::$financingOrder->requireVerification(false)->commit();

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
        self::$financingOrder->requireVerification(false)->commit();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::ContractSigned);

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

        $this->assertTrue(self::$traderOrder->hasMedia(TraderOrderMediaCollection::SignedClientWakala));
    }

    /**
     * @return void
     */
    public function test_make_order_cannot_reprocessed_on_client_wakala_accepted(): void
    {
        self::$financingOrder->requireVerification(false)->commit();

        TraderOrderScenario::of(self::$traderOrder)
            ->reset()
            ->moveToStep(MurabhaStep::MurabhaOfferIssued);

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
