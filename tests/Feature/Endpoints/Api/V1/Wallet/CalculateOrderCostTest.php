<?php

namespace Tests\Feature\Endpoints\Api\V1\Wallet;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;
use Tests\Traits\InteractsWithSettings;

class CalculateOrderCostTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender, InteractsWithSettings;

    protected static Company $company;

    protected static Company $notApprovedCompany;

    public static User $lenderAdminUserNotApproved;

    public static User $lenderAdminUserNotVerified;

    public static User $lenderAdminUser;

    public static User $lenderSupervisorUser;

    public static User $lenderBillingUser;

    public static User $lenderApiUser;

    public static User $lenderCreatorUser;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        [self::$company, $_] = $this->createCompany();
        [self::$notApprovedCompany, $_] = $this->createCompany(data: ['company_cr' => '12345678911', 'status' => CompanyStatus::Pending]);
        self::$lenderAdminUserNotApproved = $this->createLenderUser(self::$notApprovedCompany->id, Role::LenderAdmin);
        self::$lenderAdminUserNotVerified = $this->createLenderUser(self::$company->id, Role::LenderAdmin, data: ['email_verified_at' => null]);
        self::$lenderAdminUser = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$lenderSupervisorUser = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$lenderBillingUser = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$lenderCreatorUser = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_calculate_order_cost_calculation_and_response(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderAdminUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->formatByDecimal(),
                ],
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
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

    public function test_calculate_order_can_lender_supervisor_access(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderSupervisorUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->formatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_can_lender_billing_access(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderBillingUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->formatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_can_lender_api_access(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderApiUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->formatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_can_lender_order_creator_access(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderCreatorUser)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'amount' => $total->formatByDecimal(),
                ],
            ]);
    }

    public function test_calculate_order_cant_lender_not_verified_access(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderAdminUserNotVerified)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson([
                'code' => 1008,
                'message' => 'You must verify your email address',
            ]);
    }

    public function test_calculate_order_cant_lender_not_approved_access(): void
    {
        $orderCount = rand(1, 200);
        /** @var Money $orderCost */
        $orderCost = self::$company->order_cost;
        $total = $orderCost->multiply($orderCount);

        $this->actingAs(self::$lenderAdminUserNotApproved)
            ->withHeader('X-Company', self::$notApprovedCompany->id)
            ->postJson('api/v1/lender/wallet/calculate', [
                'orders_count' => $orderCount,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson([
                'message' => 'The company is not active',
                'code' => 1015,
            ]);
    }
}
