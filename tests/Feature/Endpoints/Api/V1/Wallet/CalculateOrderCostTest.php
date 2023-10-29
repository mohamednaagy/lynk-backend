<?php

namespace Tests\Feature\Endpoints\Api\V1\Wallet;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\TieredPricing;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class CalculateOrderCostTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany, InteractsWithSettings;

    protected static Company $company;

    protected static Company $notApprovedCompany;

    public static User $lenderAdminUserNotApproved;

    public static User $lenderAdminUserNotVerified;

    public static User $lenderAdminUser;

    public static User $lenderSupervisorUser;

    public static User $lenderBillingUser;

    public static User $lenderApiUser;

    public static User $lenderCreatorUser;

    public static Money $approvedCompanyOrderCostWithVat;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$company = $this->createLenderCompanyWithStandardOrderCost();

        [self::$notApprovedCompany] = $this->createCompany(data: ['company_cr' => '12345678911', 'status' => CompanyStatus::Pending]);

        self::$lenderAdminUserNotApproved = $this->createLenderUser(self::$notApprovedCompany->id);
        self::$lenderAdminUserNotVerified = $this->createLenderUser(self::$company->id, data: ['email_verified_at' => null]);
        self::$lenderAdminUser = $this->createLenderUser(self::$company->id);
        self::$lenderSupervisorUser = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$lenderBillingUser = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$lenderCreatorUser = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);

        self::$approvedCompanyOrderCostWithVat = TieredPricing::getOrderCostIfStandard(self::$company)['costWithVat'];
    }

    /**
     * A basic feature test example.
     */
    public function test_calculate_order_cost_calculation_and_response(): void
    {
        $orderCount = rand(1, 200);

        $total = self::$approvedCompanyOrderCostWithVat->multiply($orderCount);

        $this->actingAs(self::$lenderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->convertAndFormatByDecimal(),
                ],
            ]);
    }

    /**
     * A basic feature test example.
     */
    public function test_calculate_order_cost_validation_rule(): void
    {
        $this->actingAs(self::$lenderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => 0,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('orders_count');

        $this->actingAs(self::$lenderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => null,
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('orders_count');
    }

    public function test_calculate_order_lender_supervisor_can_access(): void
    {
        $orderCount = rand(1, 200);

        $total = self::$approvedCompanyOrderCostWithVat->multiply($orderCount);

        $this->actingAs(self::$lenderSupervisorUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->convertAndFormatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_lender_billing_can_access(): void
    {
        $orderCount = rand(1, 200);

        $total = self::$approvedCompanyOrderCostWithVat->multiply($orderCount);

        $this->actingAs(self::$lenderBillingUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->convertAndFormatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_with_lender_api_user_can_access(): void
    {
        $orderCount = rand(1, 200);

        $total = self::$approvedCompanyOrderCostWithVat->multiply($orderCount);

        $this->actingAs(self::$lenderApiUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->convertAndFormatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_with_lender_order_creator_cant_access(): void
    {
        $orderCount = rand(1, 200);

        $this->actingAs(self::$lenderCreatorUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    public function test_calculate_order_lender_with_not_verified_email_cannot_access(): void
    {
        $orderCount = rand(1, 200);

        $this->actingAs(self::$lenderAdminUserNotVerified)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson([
                'code' => 1008,
                'message' => __('error.must_verify_email'),
            ]);
    }

    public function test_calculate_order_lender_of_not_approved_company_cannot_access(): void
    {
        $orderCount = rand(1, 200);

        $this->actingAs(self::$lenderAdminUserNotApproved)
            ->withHeader('X-Company', self::$notApprovedCompany->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson([
                'message' => __('error.company_not_active'),
                'code' => 1015,
            ]);
    }
}
