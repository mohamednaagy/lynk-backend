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
use Illuminate\Testing\Fluent\AssertableJson;
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
    public function test_that_unauth_user_cant_cancel_order(): void
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
    public function test_cancel_order_for_auth_user_has_lender_supervisor_role(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => 'test reason',
            ]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );

        $this->assertEquals('test reason', self::$financingOrder->fresh()->status_reason);
    }

    /**
     * @return void
     */
    public function test_cancel_order_for_auth_user_has_lender_api_user_role(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_lender_billing_cannot_cancel_order(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_lender_order_creator_can_cancel_order(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('data')->where('data', [])
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_user_with_not_verified_email_cannot_cancel_order(): void
    {
        // update user email verified at to be null
        self::$userLender->email_verified_at = null;
        self::$userLender->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('error.must_verify_email'))
                    ->where('code', 1008)
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_user_when_company_not_active_cannot_cancel_order(): void
    {
        // update user email verified at to be null
        self::$company->status = CompanyStatus::Pending;
        self::$company->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('error.company_not_active'))
                    ->where('code', 1015)
            );
    }

    /**
     * @return void
     */
    public function test_cancel_order_on_empty_status_reason(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::$orderCancledUrl, [
                'status_reason' => '',
            ])
            ->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('data')->where('data', [])
            );
    }
}
