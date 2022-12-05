<?php

namespace Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\CompanyStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ApproveOrderTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static string $apiUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$apiUrl = 'api/v1/lender/orders/'.self::$financingOrder->getOriginal('id').'/approve';
    }

    public function test_order_approve_for_only_pending_approval_status(): void
    {
        $this->assertEquals(self::$financingOrder->getRawOriginal('status'), FinancingOrderStatus::PendingApproval);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    public function test_order_approve_for_auth_user(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    public function test_approve_approved_order_for_auth_user(): void
    {
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, [
            'status' => FinancingOrderStatus::WaitingClientWakala,
        ]);
        self::$apiUrl = 'api/v1/lender/orders/'.self::$financingOrder->getOriginal('id').'/approve';

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(400)->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'This order is already approved')
        );
    }

    public function test_approve_order_for_auth_lender_admin_user(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    public function test_approve_order_for_auth_lender_supervisor_user(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('data')->where('data', [])
        );
    }

    public function test_approve_order_for_UnAuth_lender_billing_user(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(403)->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'User does not have the right roles.')
                ->etc()
        );
    }

    public function test_approve_order_for_UnAuth_lender_creator_user(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(403)->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'User does not have the right roles.')
                ->etc()
        );
    }

    public function test_approve_order_for_UnAuth_lender_api_user(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(403)->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'User does not have the right roles.')
                ->etc()
        );
    }

    public function test_approve_order_on_company_UnApproved_status_auth_user(): void
    {
        self::$company->status = CompanyStatus::Pending;
        self::$company->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(403)->assertJson(
            fn (AssertableJson $json) => $json->where('message', __('error.company_not_active'))
                ->where('code', 1015)
        );
    }

    public function test_approve_order_on_auth_user_with_email_verified(): void
    {
        self::$userLender->email_verified_at = null;
        self::$userLender->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson(self::$apiUrl);

        $response->assertStatus(403)->assertJson(
            fn (AssertableJson $json) => $json->where('message', __('error.must_verify_email'))
                ->where('code', 1008)
        );
    }
}
