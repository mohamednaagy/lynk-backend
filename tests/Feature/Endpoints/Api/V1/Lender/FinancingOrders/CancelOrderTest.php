<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\CompanyStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    const BaseUrl = 'api/v1/lender/orders/';

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static string $orderCancledUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

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
        self::$orderCancledUrl = self::BaseUrl.self::$financingOrder->id.'/cancel';

        // create trader order
        self::$financingOrder->traderOrders()->create([
            'provider' => 'fake',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    /**
     * @return void
     */
    public function test_cannot_cancel_order_without_user(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_can_cancel_order_with_lender_admin(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => 'test reason',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::BaseUrl.self::$financingOrder->id)
            ->assertJsonFragment([
                'status_reason' => 'test reason',
            ]);
    }

    /**
     * @return void
     */
    public function test_can_cancel_order_with_lender_supervisor(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => 'test reason',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    /**
     * @return void
     */
    public function test_can_cancel_order_with_lender_api_user(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    /**
     * @return void
     */
    public function test_cannot_cancel_order_with_lender_billing(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    /**
     * @return void
     */
    public function test_cannot_cancel_order_with_lender_order_creator(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    /**
     * @return void
     */
    public function test_cannot_cancel_order_with_not_verified_email_user(): void
    {
        // update user email verified at to be null
        self::$userLender->email_verified_at = null;
        self::$userLender->save();

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => 1008,
            ]);
    }

    /**
     * @return void
     */
    public function test_cannot_cancel_order_with_company_not_active(): void
    {
        // update user email verified at to be null
        self::$company->status = CompanyStatus::Pending;
        self::$company->save();

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'message' => __('error.company_not_active'),
                'code' => 1015,
            ]);
    }

    /**
     * @return void
     */
    public function test_cancel_order_with_empty_status_reason(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => '',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data', []);
    }

    public function test_can_cancel_order_with_cancellable_statuses()
    {
        $statuses = [
            FinancingOrderStatus::Rejected,
            FinancingOrderStatus::Approved,
            FinancingOrderStatus::RespondedToPtp,
            FinancingOrderStatus::PendingApproval,
            FinancingOrderStatus::CommodityPurchased,
            FinancingOrderStatus::WaitingClientWakala,
            FinancingOrderStatus::PtpDocumentRetrieved,
            FinancingOrderStatus::ClientWakalaCompleted,
            FinancingOrderStatus::CommoditySoldToCustomer,
            FinancingOrderStatus::WaitingPurchasingCommodity,
        ];
        foreach ($statuses as $status) {
            self::$financingOrder->update(['status' => $status]);
            $this->actingAs(self::$userLender)
                ->withHeader('X-Company', self::$company->id)
                ->putJson(self::$orderCancledUrl)
                ->assertStatus(Response::HTTP_OK)
                ->assertJsonPath('data', []);
        }
    }

    public function test_cannot_cancel_order_with_not_cancellable_statuses()
    {
        $statuses = [
            FinancingOrderStatus::Cancelled,
            FinancingOrderStatus::Completed,
            FinancingOrderStatus::MurabhaOfferIssued,
            FinancingOrderStatus::MurabahaSaleCompleted,
            FinancingOrderStatus::ContractSigned,
            FinancingOrderStatus::PendingCancellation,
        ];
        foreach ($statuses as $status) {
            self::$financingOrder->update(['status' => $status]);
            $this->actingAs(self::$userLender)
                ->withHeader('X-Company', self::$company->id)
                ->putJson(self::$orderCancledUrl)->assertStatus(Response::HTTP_FORBIDDEN)
                ->assertJsonFragment([
                    'message' => __('error.unable_to_cancel_order'),
                    'code' => 1010,
                ]);
        }
    }
}
