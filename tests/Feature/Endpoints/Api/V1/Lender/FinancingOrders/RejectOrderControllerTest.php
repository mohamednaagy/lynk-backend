<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class RejectOrderControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $secondCompany;

    private static Wallet $wallet;

    private static Wallet $secondWallet;

    private static User $userLenderAdmin;

    private static User $customer;

    private static User $userLenderAdminWithoutEmailVerification;

    private static array $orderDetails;

    private static FinancingOrder $financingOrder;

    private static FinancingOrder $pendingApprovalOrder;

    private static string $statusReason;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000');
        [self::$secondCompany, self::$secondWallet] = $this->createCompany('3000', ['company_cr' => '12345678911']);

        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderAdminWithoutEmailVerification = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            'userLenderAdminWithoutEmailVerification@bim.com',
            ['email_verified_at' => null]
        );
        self::$customer = $this->createLenderUser(self::$company->id, Role::Customer, 'customer@bim.com');
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, []);
        self::$pendingApprovalOrder = $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::PendingApproval]);
        self::$statusReason = Str::random(80);
    }

    public function test_order_rejected_successfully()
    {
        $this->actingAs(self::$userLenderAdmin);

        $response = $this->putJson(
            '/api/v1/lender/orders/'.self::$pendingApprovalOrder->id.'/reject',
            ['status_reason' => self::$statusReason],
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(200);
    }

    public function test_order_can_not_moved_to_reject_status()
    {
        $this->actingAs(self::$userLenderAdmin);

        $response = $this->putJson(
            '/api/v1/lender/orders/'.self::$financingOrder->id.'/reject',
            ['status_reason' => self::$statusReason],
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(400)->assertJsonFragment([
            'message' => __('error.order_cannot_be_approved_because_it_is_approved'),
        ]);
    }

    public function test_other_company_can_not_reject_order()
    {
        $this->actingAs(self::$userLenderAdmin);

        $response = $this->putJson(
            '/api/v1/lender/orders/'.self::$financingOrder->id.'/reject',
            ['status_reason' => self::$statusReason],
            ['X-Company' => self::$secondCompany->id]
        );

        $response->assertStatus(404);
    }

    public function test_order_not_exists()
    {
        $this->actingAs(self::$userLenderAdmin);

        $response = $this->putJson(
            '/api/v1/lender/orders/'. 400 .'/reject',
            ['status_reason' => self::$statusReason],
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(404);
    }

    public function test_customer_can_not_reject_order()
    {
        $this->actingAs(self::$customer);

        $response = $this->putJson(
            '/api/v1/lender/orders/'.self::$financingOrder->id.'/reject',
            ['status_reason' => self::$statusReason],
            ['X-Company' => self::$company->id]
        );

        $response->assertStatus(403);
    }

    public function test_can_not_reject_order_without_email_verification()
    {
        $this->actingAs(self::$userLenderAdminWithoutEmailVerification);

        $response = $this->putJson(
            '/api/v1/lender/orders/'.self::$financingOrder->id.'/reject',
            [],
            ['X-Company' => self::$company->id]
        );
        $response->assertStatus(403)->assertJsonFragment([
            'message' => __('error.must_verify_email'),
            'code' => ErrorCode::EMAIL_NOT_VERIFIED,
        ]);
    }
}
